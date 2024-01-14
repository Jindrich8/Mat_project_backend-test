<?php

namespace App\Http\Controllers;

use App\Models\SavedTask;
use App\Http\Requests\StoreSavedTaskRequest;
use App\Http\Requests\UpdateSavedTaskRequest;

class SavedTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSavedTaskRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(SavedTask $savedTask)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SavedTask $savedTask)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSavedTaskRequest $request, SavedTask $savedTask)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SavedTask $savedTask)
    {
        //
    }
}
