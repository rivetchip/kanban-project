<?php

namespace App;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use App\Models\UserModel;

use RuntimeException;

/**
 * Authentication Controller
 * User-profile related tasks
 *
 * @package  CoffeeTask
 * @version  v.1 (12/02/2018)
 * @author   rivetchip
 */
class AuthenticationController extends AbstractController {


    /**
     * Login page
     * 
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function login( Request $request, Response $response )
    {
        if( $this->isLogged ) return redirect('/projects'); // check logged

        $this->title = 'Connexion';

        return render('login', $this->container);
    }

    /**
     * Register page
     * 
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function register( Request $request, Response $response )
    {
        if( $this->isLogged ) return redirect('/projects'); // check logged

        $this->title = 'Inscription';

        return render('register', $this->container);
    }

    /**
     * Validate login page
     * 
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function validateLogin( Request $request, Response $response )
    {
        if( $this->isLogged ) return redirect('/projects'); // check logged

        $this->title = 'Connexion';


        $email = $request->input('email');
        $password = $request->input('password');

        // create session user
        if( $email && $password )
        {
            $passHash = passhash($password);

            if( $userId = UserModel::getIdByLogin($email, $passHash) )
            {
                // try login
                session('isLogged', true);
                session('userId', $userId);
                
                return redirect('/projects');

                //TODO : last_login : date
            }
            else
            {
                $this->errors = 'Pseudo ou pass incorrect';
            }
        }
        else
        {
            $this->errors = 'Veuillez renseigner des informations';
        }


        return render('login', $this->container);
    }

    /**
     * Validate register page
     * 
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function validateRegister( Request $request, Response $response )
    {
        if( $this->isLogged ) return redirect('/projects'); // check logged

        $this->title = 'Inscription';


        $fullname = $request->input('fullname');
        $email = $request->input('email');

        $password1 = $request->input('password1');
        $password2 = $request->input('password2');


        if( empty($fullname) )
        {
            $this->errors = 'Vous devez renseigner votre nom';
        }

        if( !empty($email) )
        {
            if( $tryUserId = UserModel::getIdByEmail($email) )
            {
                $this->errors = 'Cet email est déjà utilisé';
            }
        }
        else
        {
            $this->errors = 'Vous devez renseigner votre email';
        }

        if( !empty($password1) && !empty($password2) )
        {
            if( $password1 != $password2 )
            {
                $this->errors = 'Vos mots de passe ne correspondent pas';
            }
        }
        else
        {
            $this->errors = 'Vous devez renseigner un mot de pass';
        }

        // no errors : validate

        if( !isset($this->errors) )
        {
            $passHash = passhash($password1);

            $userData = [
                'fullname' => $fullname,
                'email' => $email,
                'password' => $passHash
            ];

            $newUser = new UserModel($userData);

            $userId = $newUser->create();

            // try login
            session('isLogged', true);
            session('userId', $userId);
            
            return redirect('/projects');
        }


        return render('register', $this->container);
    }


    /**
     * Display and resize user avatar
     *
     * @param Request $request
     * @param Response $response
     * @param int $userId
     * @return void
     */
    public function avatar( Request $request, Response $response, $userId )
    {
        $uploadFolder = getcwd() . '/uploads/';

        $attachmentFolder = $uploadFolder.'avatars/';

        $default = 'avatar_default.png';

        $fileName = $attachmentFolder . $userId; // FIXME security

        if( !is_readable($fileName) )
        {
            $fileName = $uploadFolder.$default;
        }

        list($resizeWidth, $resizeHeight) = [200, 200];

        // $fileType = mime_content_type($fileName);
        // $fileSize = filesize($fileName);

        list($imageWidth, $imageHeight, $imageType) = getimagesize($fileName);

        switch( $imageType )
        {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($fileName);
                $displayCallback = 'imagejpeg';
                $contentType = 'image/jpeg';
            break;

            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($fileName);
                $displayCallback = 'imagegif';
                $contentType = 'image/gif';
            break;

            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($fileName);
                $displayCallback = 'imagepng';
                $contentType = 'image/png';
            break;

            default:
                $image = imagecreatefromjpeg($fileName);
                $displayCallback = 'imagejpeg';
                $contentType = 'image/jpeg';
            break;
        }

        // $resizeWidth = 100;
        // $ratio = $resizeWidth / $imageWidth;
        // $resizeHeight = $imageHeight * $ratio;

        //ob_start();

        $new_image = imagecreatetruecolor($resizeWidth, $resizeHeight);

        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);

        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $imageWidth, $imageHeight);

        header('Content-Type:' . $contentType);

        call_user_func($displayCallback, $new_image);

        imagedestroy($new_image);

        // $result = ob_get_clean();

        exit;

        //header('Content-Length: ' . $fileSize);
        //exit(readfile($fileName));
    }

    /**
     * Profile page
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function profile( Request $request, Response $response )
    {
        if( !$this->isLogged ) return redirect('/login?back=1'); // check logged

        $this->title = 'Profile';


        return render('profile', $this->container);
    }

    /**
     * Validate profile page
     *
     * @param Request $request
     * @param Response $response
     * @return string
     */
    public function validateProfile( Request $request, Response $response )
    {
        if( !$this->isLogged ) return redirect('/login?back=1'); // check logged

        $this->title = 'Profile';

        $userId = $this->userId;

        $fullname = !empty($_POST['fullname']) ? $_POST['fullname'] : null;//$request->input('fullname');
        $age =  !empty($_POST['age']) ? $_POST['age'] : null;//$request->input('age');
        $phonenumber =  !empty($_POST['phone_number']) ? $_POST['phone_number'] : null;//$request->input('phone_number');
        $fonction =  !empty($_POST['fonction']) ? $_POST['fonction'] : null;//$request->input('fonction');
        $password1 = !empty($_POST['password']) ? $_POST['password'] : null;//$request->input('password');
        $password2 = !empty($_POST['password2']) ? $_POST['password2'] : null;//$request->input('password2');

        //$avatar = $request->input('avatar');

        // change avatar

        if( !empty($_FILES['avatar']) && !empty($_FILES['avatar']['type']) )
        {
            $attachmentFolder = getcwd() . '/uploads/avatars/';

            $file = $_FILES['avatar'];

            if( !is_writable($attachmentFolder) )
            {
                throw new RuntimeException('Folder is not writable');
            }

            switch( $file['error'] )
            {
                case UPLOAD_ERR_OK: // 0
                    break;

                case UPLOAD_ERR_INI_SIZE: // 1
                case UPLOAD_ERR_FORM_SIZE: // 2
                    throw new RuntimeException('Exceeded filesize limit');

                case UPLOAD_ERR_PARTIAL: // 3
                    throw new RuntimeException('File was only partially uploaded');
                
                case UPLOAD_ERR_NO_FILE: // 4
                    throw new RuntimeException('No file sent');

                case  UPLOAD_ERR_NO_TMP_DIR: // 6
                    throw new RuntimeException('Cannot find tmp');
                
                case  UPLOAD_ERR_CANT_WRITE: // 7
                    throw new RuntimeException('Failed to write file to disk tmp');

                default:
                    throw new RuntimeException('Unknown errors');
            }

            // Check MIME Type by yourself

            // $finfo = new finfo(FILEINFO_MIME_TYPE);
            // if( false === $ext = array_search(
            //     $finfo->file($_FILES['upfile']['tmp_name']),
            //     array(
            //         'jpg' => 'image/jpeg',
            //         'png' => 'image/png',
            //         'gif' => 'image/gif',
            //     ),
            //     true
            // ) ) {
            //     throw new RuntimeException('Invalid file format.');
            // }

            $tmpName  = $file['tmp_name'];
            $fileName = $userId;
            
            if( !move_uploaded_file($tmpName, $attachmentFolder . $fileName) )
            {
                throw new RuntimeException('Failed to move uploaded file');
            }

            // chmod the file to be readable by everyone

            chmod($attachmentFolder . $fileName, 0665);
        }

        // change email

        if( !empty($email) )
        {
            $userData['email'] = $email;
        }

        // change fonction

        if( !empty($fonction) )
        {
            $userData['address'] = $fonction;
        }

        // change phone number

        if( !empty($phonenumber) )
        {
            $userData['phone_number'] = $phonenumber;
        }

        // change age

        if( !empty($age) )
        {
            $userData['age'] = $age;
        }

        // change name

        if( !empty($fullname) )
        {
            $userData['fullname'] = $fullname;
        }

        // change the password

        if( !empty($password1) && !empty($password2) )
        {
            if( $password1 == $password2 )
            {
                $userData['password'] = $password1;
            }
            else
            {
                $this->errors = 'Vos mots de passe ne correspondent pas';
            }
        }

        // save user

        if( !isset($this->errors) )
        {
            if( !empty($userData) )
            {
                $user = UserModel::find($userId);

                if( isset($userData['password']) )
                {
                    $userData['password'] = passhash($password1);
                }

                $user->attributes($userData);
    
                $user->save();
            }
            
            $this->messages = 'Profil mis à jour!';
        }

        // reinit user datas

        $this->user = UserModel::find($userId);

        return render('profile', $this->container);
    }



    /**
     * Logout page / user
     *
     * @return void
     */
    public function logout()
    {
        session_destroy();

        return redirect('/?logout=1'); //home
    }


}
