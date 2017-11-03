<?php

namespace App;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use App\Models\ProjectsModel;
use App\Models\TasksModel;

use DateTime;


class ApiController extends AbstractController {

    /**
     * Api error message
     *
     * @param string $code
     * @return string
     */
    protected function apiError( $code )
    {
        $messages = [
            'UserNotLogged' => 'User not logged',
            'ProjectNotExist' => 'Project does not exist',
            'ProjectNotCreated' => 'Project cannot be created',
            'TaskNotCreated' => 'Task cannot be created',
            'TaskNotDeleted' => 'Task cannot be deleted',
        ];

        return json(['error' => [ 'code' => $code, 'message' => $messages[$code] ]]);
    }

    protected function defineRole( $projectId )
    {
        $role = 'user';




        return $role;
    }


    /**
     * Heartbeat function
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function heartbeat( Request $request, Response $response )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $data = [];



        return json($data);
    }



//////// PROJECTS

    /**
     * Get all projects
     *
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function projectsList( Request $request, Response $response )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $results = ProjectsModel::getAllByUser($this->userId);

        return json($results);
    }

    public function projectPeoples( Request $request, Response $response, $projectId )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $results = ProjectsModel::getPeoples($projectId);
        
        return json($results);
    }

    public function projectCreate( Request $request, Response $response )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $pTitle = $request->input('title', '');
        $pDescription = $request->input('description', '');

        $pUsers = $request->input('users', '');
        $pUsers = preg_split("/\\r\\n|\\r|\\n/", $pUsers);

        $pUsers = array_unique($pUsers);

        $pManager = $request->input('manager', '');

        $data = [
            'title' => $pTitle,
            'description' => $pDescription,
            'manager' => $pManager,
            'users' => $pUsers,
        ];

        $userId = $this->userId;

        $project = ProjectsModel::createNew($userId, $data);

        if( $project ) {

            return json(['message'=>'ok']);
        }

        return $this->apiError('ProjectNotCreated');
    }

    public function projectUpdate( Request $request, Response $response )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $data = [];






        return json($data);
    }

    public function projectDelete( Request $request, Response $response, $projectId )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $data = [];

        $project = ProjectsModel::find($projectId);

        if( $project ) {

            $project->delete();

            return json(['message'=>'ok']);
        }
        else {
            return $this->apiError('ProjectNotExist');
        }

        return json($data);
    }



//////// CATEGORIES

    public function categoriesList( Request $request, Response $response, $projectId )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        $project = ProjectsModel::find($projectId);
        
        if( $project ) {

            $categories = ProjectsModel::findCategories($projectId);

            return json($categories);
        }

        return $this->apiError('ProjectNotExist');
    }

    /**
     * Get tasks from a category and a project
     *
     * @param Request $request
     * @param Response $response
     * @param int $projectId
     * @param int $categoryId
     * @return string
     */
    public function categoriesTasksList( Request $request, Response $response, $projectId, $categoryId )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        // get task

        $userId = $this->userId;

        $results = TasksModel::getAllFromProjectCategoryUser($projectId, $categoryId, $userId);
        
        return json($results);
    }


    public function categoryCreate( Request $request, Response $response )
    {
        $data = [];




        return json($data);
    }

    public function categoryUpdate( Request $request, Response $response )
    {
        $data = [];




        return json($data);
    }

    public function categoryDelete( Request $request, Response $response )
    {
        $data = [];





        return json($data);
    }


//////// TASKS

    public function taskGet( Request $request, Response $response, $taskId )
    {
        $data = [];



        return json($data);
    }

    public function taskCreate( Request $request, Response $response, $projectId, $categoryId )
    {
        if( !$this->isLogged ) return $this->apiError('UserNotLogged');

        //$input = $request->getBodyParams();

        $assignedTo = $request->input('newtask-field-people');

        if( empty($assignedTo) ) // if not assigned (0) set to self
        {
            $assignedTo = $this->userId;
        }

        $dateEndAt = $request->input('newtask-field-end-at');

        if( empty($dateEndAt) ) // current date + 1week
        {
            //$date = (new DateTime())->modify('+1 week');
            //$dateEndAt = $date->format(DatabaseDatetime());
            $dateEndAt = null;
        }

        $task = new TasksModel([
            'project_id' => $projectId,
            'category_id' => $categoryId,
            'assigned_to' => $assignedTo,

            'title' => $request->input('newtask-field-title'),
            'description' => $request->input('newtask-field-description'),
            'end_at' => $dateEndAt,
        ]);

        $taskId = $task->create();

        if( $taskId )
        {
            return json(['message'=>'ok', 'taskId' => $taskId]);
        }

        return $this->apiError('TaskNotCreated');
    }

    public function taskUpdate( Request $request, Response $response, $taskId )
    {



    }

    public function taskDelete( Request $request, Response $response, $taskId )
    {
        $task = TasksModel::find($taskId);

        //@FIXME : search if the usetr who delted the task is the user who is asigned to the task ; or is admin

        if( $task )
        {
            // delete db

            $result = $task->delete();

            if( $result )
            {
                return json(['message'=>'ok']);
            }
        }

        return $this->apiError('TaskNotDeleted');
    }

    public function taskMove( Request $request, Response $response, $taskId, $projectId, $categoryId )
    {
        $data = [];




        return json($data);
    }

    



}
