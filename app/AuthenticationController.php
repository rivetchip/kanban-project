<?php

namespace App;

use Slim\Http\Interfaces\RequestInterface as Request;
use Slim\Http\Interfaces\ResponseInterface as Response;

use App\Models\UserModel;



class AuthenticationController extends AbstractController {


    /**
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




    public function profile( Request $request, Response $response )
    {
        if( !$this->isLogged ) return redirect('/login?back=1'); // check logged

        $this->title = 'Profile';

        $userId = $this->userId;
        

        return render('profile', $this->container);
    }

    public function validateProfile( Request $request, Response $response )
    {
        if( !$this->isLogged ) return redirect('/login?back=1'); // check logged

        $this->title = 'Profile';

        $userId = $this->userId;


        $fullname = $request->input('fullname');
        $email = $request->input('email');

        $password1 = $request->input('password');
        $password2 = $request->input('password2');

        // change email

        if( !empty($email) )
        {
            $userData['email'] = $email;
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

            $this->messages = 'Profile mis à jour!';
        }

        return render('profile', $this->container);
    }











    public function logout()
    {
        session_destroy();

        return redirect('/?logout=1'); //home
    }


}
