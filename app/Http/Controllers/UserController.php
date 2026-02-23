<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

class UserController extends \Illuminate\Routing\Controller
{
    use BreadRelationshipParser;

    /*
    |--------------------------------------------------------------------------
    | SHARED HELPER
    | Resolve Voyager DataType — used to fire BREAD events with correct context
    |--------------------------------------------------------------------------
    */
    private function getDataType(): object
    {
        return Voyager::model('DataType')
            ->where('slug', 'users')
            ->firstOrFail();
    }


    /*
    |--------------------------------------------------------------------------
    | BROWSE — GET /api/users
    | Returns paginated/filtered user list.
    | Frontend sends: ?s=, ?key=, ?filter=, ?order_by=, ?sort_order=,
    |                 ?showSoftDeleted=1, ?role=
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $dataType  = $this->getDataType();
        $getter    = $dataType->server_side ? 'paginate' : 'get';
        $orderBy   = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', $dataType->order_direction);
        $search    = (object) [
            'value'  => $request->get('s', ''),
            'key'    => $request->get('key', ''),
            'filter' => $request->get('filter', ''),
        ];

        $query = User::select('users.*');

        // Soft deletes
        $usesSoftDeletes  = false;
        $showSoftDeleted  = false;
        if (in_array(SoftDeletes::class, class_uses_recursive(User::class))) {
            $usesSoftDeletes = true;
            if ($request->boolean('showSoftDeleted')) {
                $showSoftDeleted = true;
                $query->withTrashed();
            }
        }

        // Optional role filter
        if ($request->filled('role')) {
            $query->whereHas('role', fn($q) => $q->where('name', $request->role));
        }

        // Search
        if ($search->value !== '' && $search->key && $search->filter) {
            $operator    = $search->filter === 'equals' ? '=' : 'LIKE';
            $searchValue = $search->filter === 'equals'
                ? $search->value
                : '%' . $search->value . '%';

            // Relationship search
            if ($row = $this->findSearchableRelationshipRow(
                $dataType->rows->where('type', 'relationship'), $search->key
            )) {
                $query->whereIn(
                    'users.' . $search->key,
                    $row->details->model::where($row->details->label, $operator, $searchValue)
                        ->pluck('id')->toArray()
                );
            } elseif ($dataType->browseRows->pluck('field')->contains($search->key)) {
                $query->where('users.' . $search->key, $operator, $searchValue);
            }
        }

        // Ordering with optional relationship join
        $relationshipRow = $dataType->rows->where('field', $orderBy)->firstWhere('type', 'relationship');
        if ($orderBy && (in_array($orderBy, $dataType->fields()) || !empty($relationshipRow))) {
            $dir = $sortOrder ?: 'desc';
            if (!empty($relationshipRow)) {
                $query->select([
                    'users.*',
                    'joined.' . $relationshipRow->details->label . ' as ' . $orderBy,
                ])->leftJoin(
                    $relationshipRow->details->table . ' as joined',
                    'users.' . $relationshipRow->details->column,
                    'joined.' . $relationshipRow->details->key
                );
            }
            $dataTypeContent = call_user_func([$query->orderBy($orderBy, $dir), $getter]);
        } elseif ((new User())->timestamps) {
            $dataTypeContent = call_user_func([$query->latest(User::CREATED_AT), $getter]);
        } else {
            $dataTypeContent = call_user_func([$query->orderBy((new User())->getKeyName(), 'DESC'), $getter]);
        }

        $dataTypeContent->load(['role', 'Post']);

        // Sortable columns and search names for frontend table headers
        $searchNames      = $dataType->browseRows->mapWithKeys(
            fn($row) => [$row['field'] => $row->getTranslatedAttribute('display_name')]
        );

        $showCheckboxColumn = true; // frontend controls visibility
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first()
                + ($showCheckboxColumn ? 1 : 0);
            $orderColumn = [[$index, $sortOrder ?? 'desc']];
        }

        return response()->json([
            'dataType'           => $dataType,
            'data'               => $dataTypeContent,
            'search'             => $search,
            'searchNames'        => $searchNames,
            'orderBy'            => $orderBy,
            'orderColumn'        => $orderColumn,
            'sortOrder'          => $sortOrder,
            'usesSoftDeletes'    => $usesSoftDeletes,
            'showSoftDeleted'    => $showSoftDeleted,
            'showCheckboxColumn' => $showCheckboxColumn,
            'isServerSide'       => isset($dataType->server_side) && $dataType->server_side,
            'defaultSearchKey'   => $dataType->default_search_key ?? null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — POST /api/users
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $dataType = $this->getDataType();

        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'mobileno'     => 'nullable|string|max:25',
            'password'     => 'required|string|min:8',
            'role_id'      => 'nullable|exists:roles,id',
            'post'         => 'nullable|exists:post_master,post_id',
            'highestquali' => 'nullable|string|max:50',
            'ssc'          => 'nullable|string|max:10',
            'hsc'          => 'nullable|string|max:10',
            'diploma'      => 'nullable|string|max:10',
            'degree'       => 'nullable|string|max:10',
            'masterdegree' => 'nullable|string|max:10',
            'referenceby'  => 'nullable|string|max:100',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        event(new BreadDataAdded($dataType, $user));

        return response()->json([
            'message'  => __('voyager::generic.successfully_added_new') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'dataType' => $dataType,
            'data'     => $user->load(['role', 'Post']),
        ], 201);
    }



    /*
    |--------------------------------------------------------------------------
    | UPDATE — PUT|PATCH /api/users/{id}
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, $id)
    {
        $dataType = $this->getDataType();
        $user     = $this->baseQuery()->findOrFail($id);

        $validated = $request->validate([
            'name'             => 'sometimes|string|max:255',
            'mobileno'         => 'nullable|string|max:25',
            'role_id'          => 'nullable|exists:roles,id',
            'post'             => 'nullable|exists:post_master,post_id',
            'highestquali'     => 'nullable|string|max:50',
            'ssc'              => 'nullable|string|max:10',
            'hsc'              => 'nullable|string|max:10',
            'diploma'          => 'nullable|string|max:10',
            'degree'           => 'nullable|string|max:10',
            'masterdegree'     => 'nullable|string|max:10',
            'aptiscore'        => 'nullable|integer',
            'techroundpercent' => 'nullable|numeric',
            'interviewpercent' => 'nullable|numeric',
            'referenceby'      => 'nullable|string|max:100',
        ]);

        $user->update($validated);

        event(new BreadDataUpdated($dataType, $user));

        return response()->json([
            'message'  => __('voyager::generic.successfully_updated') . " {$dataType->getTranslatedAttribute('display_name_singular')}",
            'dataType' => $dataType,
            'data'     => $user->fresh()->load(['role', 'Post']),
        ]);
    }

}