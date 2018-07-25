<?php

namespace App\Http\Controllers;

use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Resources\TasksResource;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return TasksResource::collection(Task::all());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request,$project)
    { request()->validate([
        'name' => 'required',
        'starts_at' => 'required',
        'duration' => 'required'
    ]);
        $data = $request->all();
        $data['project_id'] = $project;
        $tasks = Task::create($data);
        return response()->json([
            'status' => 'success',
            'data' => $tasks
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return new TasksResource(Task::find($id));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id,$project)
    {
        $token = $request->header('Authorization');
        $token = substr($token, 6);
        $token = trim($token);
        $user = getUserInfo($token);
        $user_id = $user->id;
        $checkRight = checkRight($user_id,$project);
        if($checkRight){
            $task = Task::find($id);
            $task->name = $request->text;
            $task->starts_at = $request->start_date;
            $task->duration = $request->duration;
            $task->progress = $request->has("progress") ? $request->progress : 0;
            $task->save();
            return response()->json([
                "action"=> "updated"
            ]);
        } else{
            return response()->json(["message"=> "Unauthorized action"],401);

        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Task  $task
     * @return \Illuminate\Http\Response
     */

    public function destroy(Request $request,$id,$project)
    {
        $token = $request->header('Authorization');
        $token = substr($token, 6);
        $token = trim($token);
        $user = getUserInfo($token);
        $user_id = $user->id;
        $checkRight = checkRight($user_id,$project);
        if($checkRight){
            DB::table('tasks')->where('id', $id)->delete();
            InsertLog("deleteTask",$id,$user_id);
            return response()->json(null, 204);
        }else{
            return response()->json(["message"=> "Unauthorized action"],401);
        }
    }

    public function ResourceToTask(Request $request,$project)
    {
        $token = $request->header('Authorization');
        $token = substr($token, 6);
        $token = trim($token);
        $user = getUserInfo($token);
        $user_id = $user->id;
        $resource_id = "";
        $functionName = "";
        $checkRight = checkRight($user_id,$project);
        if($checkRight){
            if($request->action === "add"){
                $resource_id =$request->resource_id;
                $functionName ="AddRessourceToTask";
            }elseif($request->action === "remove"){
                $resource_id = NULL;
                $functionName ="RemoveRessourceToTask";
            }

            $data =  DB::table('tasks')
                ->where('id', $request->task_id)
                ->update(['resource_id' => $resource_id]);

            InsertLog($functionName,$request->task_id,$user_id);
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } else {
            return response()->json(["message"=> "Unauthorized action"],401);
        }
    }
}