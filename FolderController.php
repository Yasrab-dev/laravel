<?php
/**
 * Created by PhpStorm.
 * User: Yasrab
 * Date: 10/26/18
 * Time: 5:22 PM
 */

namespace App\Http\Controllers\Composer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Validation;
use App\Repository\Publish\Composer\FolderRepo;
use App\Repository\Publish\Planner\PlansRepository;
use App\Repository\Settings\WorkspaceRepo;


class FolderController extends Controller
{
    public function fetchFolders(Request $request){

        $payload = $request->all();
        $valid = $this->validateGetPayload($payload);

        if($valid['status']){
            $filters = [
                'folder_id' => null,
                'user_id' => Auth::id(),
                'workspace_id' => $payload['workspace_id'],
            ];
            $total = PlansRepository::totalPublications($filters);
            $folders = FolderRepo::fetchFolders($payload['workspace_id'], ['name']);
            if(sizeof($folders)){

                foreach ($folders as $folder){
                    $filters['folder_id'] = $folder['_id'];
                    $folder['publications'] = PlansRepository::totalPublications($filters);

                }
            }

            return response()->json(['folders'=>$folders,'total'=>$total]);
        }
        return response()->json($valid);
    }

    public function validateGetPayload($payload){

        return Validation::validatePayload([
            'rules'=>[
                'workspace_id'=>['required']
            ],
            'allowedPayload'=>['workspace_id'],
            'passedPayload'=>$payload
        ]);
    }

    public function createFolder(Request $request){

        $payload = $request->all();
        $valid = $this->validateCreatePayload($payload);

        if($valid['status']){

            if(isset($payload['editId']) && $payload['editId'] === 'edit_default_folder'){
                if(WorkspaceRepo::addSetting(['workspace_id'=>$payload['workspace_id'],'default_campaign_name'=>$payload['folder']['name']])) return response()->json(['status'=>true]);
                return response()->json(['status'=>false]);
            }

            $payload['user_id'] = Auth::id();
            $result = FolderRepo::saveFolder($payload);
            if( $result) return response()->json(['status'=>true , 'data' => $result]);
            return response()->json(['status'=>false]);

        }
        return response()->json($valid);
    }

    public function validateCreatePayload($payload){

        return Validation::validatePayload([
            'rules'=>[
                'workspace_id'=>['required'],
                'folder.name'=>['required']
            ],
            'allowedPayload'=>['workspace_id','folder','editId'],
            'passedPayload'=>$payload
        ]);
    }

    public function removeFolder(Request $request){

        $payload = $request->all();
        $valid = $this->validateRemovePayload($payload);

        if($valid['status']){

            if(FolderRepo::removeFolder($payload)){

                PlansRepository::updatePlanDetails(['folderId'=>$payload['id'],'workspace_id'=>$payload['workspace_id']],['folderId'=>null]);
                return response()->json(['status'=>true]);

            }
            return response()->json(['status'=>false]);

        }
        return response()->json($valid);
    }

    public function validateRemovePayload($payload){

        return Validation::validatePayload([
            'rules'=>[
                'workspace_id'=>['required'],
                'id'=>['required']
            ],
            'allowedPayload'=>['workspace_id','id'],
            'passedPayload'=>$payload
        ]);
    }

    public function updatePublicationFolderName(Request $request){

        $payload = $request->all();
        $valid = $this->validateUpdatePayload($payload);

        if($valid['status']){

            if( FolderRepo::updateFolder($payload['folder'])) return response()->json(['status'=>true]);
            return response()->json(['status'=>false]);

        }
        return response()->json($valid);
    }
    public function validateUpdatePayload($payload){

        return Validation::validatePayload([
            'rules'=>[
                'workspace_id'=>['required'],
                'folder.name'=>['required'],
                'folder._id'=>['required'],
                'folder.updateName'=>['required']
            ],
            'allowedPayload'=>['workspace_id','folder'],
            'passedPayload'=>$payload
        ]);
    }

    public function updateDefaultPublicationFolderName(Request $request){

        $payload = $request->all();
        $valid = $this->validateDefaultUpdatePayload($payload);

        if($valid['status']){

            if(WorkspaceRepo::addSetting(['workspace_id'=>$payload['workspace_id'],'default_campaign_name'=>$payload['name']])) return response()->json(['status'=>true]);
            return response()->json(['status'=>false]);

        }
        return response()->json($valid);
    }
    public function validateDefaultUpdatePayload($payload){

        return Validation::validatePayload([
            'rules'=>[
                'workspace_id'=>['required'],
                'name'=>['required']
            ],
            'allowedPayload'=>['workspace_id','name'],
            'passedPayload'=>$payload
        ]);
    }


}
