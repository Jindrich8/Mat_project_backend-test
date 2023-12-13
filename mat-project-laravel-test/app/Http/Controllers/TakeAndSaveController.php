<?php

namespace App\Http\Controllers;

use App\Dtos\Task\Task\Take\Request;
use App\Dtos\Task\Task\Take\Response;
use App\Helpers\ExerciseHelper;
use App\Models\Tag;
use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Exercise;
use App\Models\Group;
use App\Models\Resource;
use App\Models\Task;
use App\Models\User;
use App\TableSpecificData\TaskDisplay;
use App\Utils\Utils;
use Illuminate\Support\Facades\DB;
use PDO;

class TakeAndSaveController extends Controller
{


    public function take(Request\Request $request,int $id):mixed{
        
       $task = Task::whereId($id);
       
      $exercises = ExerciseHelper::take($id);
      $groups = Group::whereTaskId($id)->get();
      

        $dataEntries = [];
        
        $currentGroup = null;
        $currentDataGroup = null;
        $i = 0;
        foreach($exercises as $exercise){
            $group = $groups->shift();
            if($group->start === $i){
                $currentGroup = $group;
              $dataEntries[] = $currentDataGroup = (Response\DefsGroup::create())->setResources(
                $group->resources->map(fn(Resource $resource)=>
                (Response\DefsGroupResourcesItems::create())
                ->setContent($resource->content))
              );
            }
            //TODO: $exercise->impl->setSavedValue();
            $dataExercise = (Response\DefsExercise::create())
            ->setExerType($exercise->type->name)
            ->setInstructions(
                (Response\DefsExerciseInstructions::create())
            ->setContent($exercise->instructions)
            )
            ->setContent($exercise->impl->toArray());
            if($currentDataGroup){
                $currentDataGroup->entries[]=$dataExercise;
            }
        }
      
        $dataTask = (new Response\DataTask())
        ->setName($task->name)
        ->setDisplay(TaskDisplay::from($task->orientation)->name)
        ->setDescription($task->description)
        ->setEntries($dataEntries);

       $response = (Response\Response::create())
       ->setData(
        (new Response\Data())->setTask($dataTask)
       );
      return Response\Response::export($response);
    }
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
    public function store(StoreTagRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTagRequest $request, Tag $tag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tag $tag)
    {
        //
    }
}
