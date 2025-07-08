<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();
        if ($roles->isEmpty()) {
            return response()->json([
                'message' => 'No roles found',
                'status_code' => 404,
                'data' => null
            ], 404);
        }

        return response()->json([
            'message' => 'Roles retrieved successfully',
            'status_code' => 200,
            'data' => $roles
        ], 200);
    }

    public function store(Request $request)
    {
        $validatedData = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255|unique:roles,name',
            ]
        );

        if ($validatedData->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => $validatedData->errors()->first(),
                'data' => null,
            ], 400);
        }

        $role = Role::create($validatedData);

        return response()->json([
            'message' => 'Role created successfully',
            'status_code' => 201,
            'data' => $role
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $validatedData = Validator::make(
            $request->all(),
            [
                'name' => 'required|string|max:255|unique:roles,name,' . $id,
            ]
        );
        if ($validatedData->fails()) {
            return response()->json([
                'status_code' => 400,
                'message' => $validatedData->errors()->first(),
                'data' => null,
            ], 400);
        }

        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status_code' => 404,
                'data' => null
            ], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
        ]);

        $role->update($validatedData);

        return response()->json([
            'message' => 'Role updated successfully',
            'status_code' => 200,
            'data' => $role
        ], 200);
    }

    public function destroy($id)
    {
        $role = Role::find($id);
        if (!$role) {
            return response()->json([
                'message' => 'Role not found',
                'status_code' => 404,
                'data' => null
            ], 404);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully',
            'status_code' => 200,
            'data' => null
        ], 200);
    }
}
