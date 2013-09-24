<?php

class Helper_User
{

    private static $_user = false;

    /**
     * @param Validation $validation
     * @param $email
     * @param $password
     * @return bool
     */
    public static function validate_login(Validation $validation, $email, $password)
    {
        if (!$validation[$email] OR !$validation[$password]) return true;
        if (!self::login($validation[$email], $validation[$password])) {
            $validation->error($email, 'validate_login');
            $validation->error($password, 'validate_login');
        }
    }

    /**
     * @param Validation $validation
     * @param $email
     */
    public static function validate_check_dbloptin(Validation $validation, $email)
    {
        $user = Doctrine::instance()->createQuery('SELECT u FROM Model_User u WHERE u.email = :email')
            ->setParameter('email', $validation[$email])
            ->getOneOrNullResult();

        if ($user AND !$user->dbloptin) {
            $validation->error($email, 'validate_check_dbloptin');
            $user->sendActivationMail();
        }
    }

    /**
     * @param Validation $validation
     * @param $email
     */
    public static function validate_check_email(Validation $validation, $email)
    {
        $user = Doctrine::instance()->createQuery('SELECT u FROM Model_User u WHERE u.email = :email')
            ->setParameter('email', $validation[$email])
            ->getOneOrNullResult();

        if ($user AND $user->is_active AND !$user->is_deleted && $user->dbloptin) {
            $user->sendNewPassword();
        } else {
            $validation->error($email, 'validate_check_email');
        }
    }

    /**
     * @param Validation $validation
     * @param $email
     * @param bool $current_email
     * @return bool
     */
    public static function validate_email_unique(Validation $validation, $email, $current_email = false)
    {
        if ($current_email AND $current_email == $validation[$email]) return true;
        if ($user = Doctrine::instance()->getRepository('Model_User')->findOneByEmail($validation[$email])) {
            $validation->error($email, 'validate_email_unique');
        }
    }

    /**
     * @param $email
     * @param $password
     * @return mixed
     */
    public static function getUserByEmailAndPassword($email, $password)
    {
        $user = Doctrine::instance()
            ->createQuery('SELECT u FROM Model_User u WHERE u.email = :email AND SHA1(CONCAT(:password, u.salt)) = u.password AND u.is_active = 1 AND u.is_deleted = 0')
            ->setParameter('email', $email)
            ->setParameter('password', $password)
            ->getOneOrNullResult();
        return $user;
    }

    /**
     * @param $email
     * @param $password
     * @return bool
     */
    public static function login($email, $password)
    {
        $user = Helper_User::getUserByEmailAndPassword($email, $password);

        if (!$user) return false;
        Session::instance()->set('user_id', $user->id);

        return true;
    }

    /**
     * Create a unique, 40 character long, not user-specific API token
     *
     * @param $user
     * @return string
     */
    public static function createTokenForUser($user)
    {
        return SHA1(uniqid());
    }

    /**
     * Returns the token for a user identified by her credentials
     *
     * @param $email
     * @param $password
     * @return string
     * @throws Kohana_Exception
     */
    public static function getUserToken($email, $password)
    {

        $user = Helper_User::getUserByEmailAndPassword($email, $password);

        if (!$user) {
            throw new Kohana_Exception("Access denied - wrong username or password", null, 403);
        }
        $token = $user->token;
        if (empty($token)) {
            $user->token = Helper_User::createTokenForUser($user);
            Doctrine::instance()->persist($user);
            Doctrine::instance()->flush();
        }

        return $user->token;
    }

    /**
     * Returns the user identified by her token or NULL
     *
     * @param $token
     * @return mixed
     */
    public static function getUserByToken($token)
    {
        $user = Doctrine::instance()
            ->createQuery('SELECT u FROM Model_User u WHERE u.token = :token AND u.is_active = 1 AND u.is_deleted = 0')
            ->setParameter('token', $token)
            ->getOneOrNullResult();
        return $user;
    }

    /**
     * @return bool
     */
    public static function logout()
    {
        if (!self::getUser()) return false;
        Session::instance()->delete('user_id');
        return true;
    }

    /**
     * @return bool
     */
    public static function getUser()
    {
        if (self::$_user) return self::$_user;

        if (!$user_id = Session::instance()->get('user_id', false))
            return self::$_user = false;

        if (!$user = Doctrine::instance()->getRepository('Model_User')->findOneById($user_id))
            return self::$_user = false;

        return self::$_user = $user;
    }

    /**
     * @return array
     */
    public static function getUsers()
    {
        return Doctrine::instance()->getRepository('Model_User')->findBy(array('is_active' => 1, 'is_deleted' => 0), array('first_name' => 'asc'));
    }

    /**
     * @param $articleId
     * @param $user
     * @return bool
     */
    public static function isFavorite($articleId, $user)
    {
        foreach ($user->favorites as $favorite) {
            if ($favorite->article->id == $articleId) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $title
     * @param $user
     * @return Model_Favoritegroup|null
     */
    public static function addFavoriteGroupForUser($title, $user)
    {
        try {
            $favGroup = new Model_Favoritegroup();
            $favGroup->name = $title;
            $favGroup->user = $user;
            Doctrine::instance()->persist($favGroup);
            Doctrine::instance()->flush();
        } catch (Exception $e) {
            // TODO: Log the error and inform dev
            return null;
        }
        return $favGroup;
    }

    /**
     * @param $groupId
     * @param $user
     * @return bool
     */
    public static function removeFavoriteGroupForUser($groupId, $user)
    {
        $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $groupId));
        if ($group) {
            Doctrine::instance()->remove($group);
            Doctrine::instance()->flush();
        }
        return true;
    }

    /**
     * @param $articleId
     * @param $groupId
     * @param $user
     * @return bool
     */
    public static function addArticleToFavoriteGroupForUser($articleId, $groupId, $user)
    {
        $success = false;
        $article = Doctrine::instance()->getRepository('Model_Article')->findOneBy(array('id' => $articleId));
        if ($article) {
            $favorite = Doctrine::instance()->getRepository('Model_Favorite')->findOneByArticle($article);
            $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $groupId));

            if ($group && $favorite && !$group->favorites->contains($favorite)) {
                $group->favorites->add($favorite);
                $favorite->favoriteGroups->add($group);
                Doctrine::instance()->persist($favorite);
                Doctrine::instance()->persist($group);
                Doctrine::instance()->flush();
                $success = true;
            }
        }

        return $success;
    }

    /**
     * @param $articleId
     * @param $groupId
     * @param $user
     * @return bool
     */
    public static function removeArticleFromFavoriteGroupForUser($articleId, $groupId, $user)
    {
        $article = Doctrine::instance()->getRepository('Model_Article')->findOneBy(array('id' => $articleId));

        if ($article) {
            $favorite = Doctrine::instance()->getRepository('Model_Favorite')->findOneByArticle($article);
            $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $groupId));
            if ($favorite && $group) {
                if ($group->favorites->contains($favorite)) {
                    $group->favorites->removeElement($favorite);
                    $favorite->favoriteGroups->removeElement($group);
                    Doctrine::instance()->persist($favorite);
                    Doctrine::instance()->persist($group);
                    Doctrine::instance()->flush();
                }
            }
        }

        return true;
    }

    /**
     * @param $articleId
     * @param $user
     * @return bool
     * @throws Kohana_Exception
     */
    public static function addArticleToUsersFavorites($articleId, $user)
    {

        $successfullyAdded = false;
        $article = Doctrine::instance()->getRepository('Model_Article')->findOneById($articleId);
        if (!$article) {
            throw new Kohana_Exception("Article could not be found", null, 404);
        }

        if (!Helper_User::isFavorite($articleId, $user)) {
            try {
                $newFav = new Model_Favorite();
                $newFav->user = $user;
                $newFav->article = $article;
                Doctrine::instance()->persist($newFav);
                Doctrine::instance()->flush();

                $successfullyAdded = true;
            } catch (Exception $e) {
                // TODO: Log the error and inform dev
            }
        } else {
            $successfullyAdded = true;
        }
        return $successfullyAdded;
    }

    /**
     * @param $articleId
     * @param $user
     * @return bool
     */
    public static function removeArticleFromUsersFavorites($articleId, $user)
    {

        foreach ($user->favorites as $favorite) {
            if ($favorite->article->id == $articleId) {
                Doctrine::instance()->remove($favorite);
            }
        }
        Doctrine::instance()->flush();
        return true;
    }

    /**
     * When a user is deleted in the backend, the corresponding data
     * entry is anonymized but kept for keeping eventual references to and
     * from other entities
     */
    public static function anonymizeUserAndMarkAsDeleted($user)
    {
        $user->first_name = Helper_Message::get("user.unknown_first_name");
        $user->last_name = Helper_Message::get("user.unknown_last_name");
        $user->email = $user->id . "@" . Helper_Message::get("user.unknown_email_domain");
        $user->password = "";
        $user->salt = "";
        $user->favorites = new Doctrine\Common\Collections\ArrayCollection;

        // Mark as both inactive and deleted
        $user->is_active = false;
        $user->is_deleted = true;

        Doctrine::instance()->persist($user);
        Doctrine::instance()->flush();
    }

    /**
     * @param $groupId
     * @param $hash
     * @throws Kohana_Exception
     */
    static function copyArticleGroup($groupId, $hash)
    {
        $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneById($groupId);
        $user = Helper_User::getUser();

        if ($group && $group->shared) {
            $hashData = $group->getSharehash();
            if ($hashData != $hash) {
                throw new Kohana_Exception('Group not found', null, 404);
            }

            $favGroup = new Model_Favoritegroup();
            $favGroup->name = $group->name;
            $favGroup->user = $user;

            Doctrine::instance()->persist($favGroup);
            Doctrine::instance()->flush();

            foreach ($group->getArticles() as $article) {
                $fav = $article->addToFavorites();
                $favGroup->favorites->add($fav);

                $fav->favoriteGroups->add($favGroup);
                Doctrine::instance()->persist($fav);
                Doctrine::instance()->persist($favGroup);
            }

            Doctrine::instance()->flush();
            Request::current()->redirect(Url::get(array(
                'route' => 'default',
                'controller' => Request::current()->controller(),
                'action' => 'index'
            )));
        }
        throw new Kohana_Exception('Group not found', null, 404);
    }

    /**
     * @param $groupId
     * @param $hash
     * @return array
     * @throws Kohana_Exception
     */
    static function showArticleGroup($groupId, $hash) {
       $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('shared' => 1, 'id' => $groupId));

       if($group) {
           $hashData = $group->getSharehash();
           if($hash == $hashData) {
               $query = Doctrine::instance()
                   ->createQueryBuilder()
                       ->select('a')
                       ->from('Model_Article', 'a')
                       ->join('a.favedBy', 'f')
                       ->join('f.favoriteGroups', 'g')
                       ->where('a.deleted != 1')
                       ->andWhere("(a.active = 1 AND a.ready_for_publish = 1) AND g = :group")
                       ->setParameter('group', $group);

               $result = $query->getQuery()
                               ->getResult();

               return array(Helper_Article::sortResult($result), $group, $hashData);
           } else {
               throw new Kohana_Exception('Group not found', null, 404);
           }
       } else {
           throw new Kohana_Exception('Group not found', null, 404);
       }
   	}

    /**
     * @param $data
     * @param bool $isUserArticleGroup
     * @return array|bool
     */
    static function newArticleGroup($data, $isUserArticleGroup = false) {

        if($data && isset($data['name'])) {
            $validate = Validation::factory($data)
                ->rule('name', 'not_empty');

            if($validate->check()) {
                $user = Helper_User::getUser();
                if($user) {
                    $favGroup = new Model_Favoritegroup();
                    $favGroup->name = $data['name'];
                    $favGroup->user = $user;
                    $favGroup->isUserArticleGroup = $isUserArticleGroup;
                    Doctrine::instance()->persist($favGroup);
                    Doctrine::instance()->flush();
                    return true;
                }
            }

           $errors = $validate->errors('validation');
           $values = $validate->as_array();
           return array($errors, $values);
        }
        return false;
    }

    /**
     * @param null $groupId
     * @param $articleId
     */
    static function addArticleToGroup($groupId = null, $articleId) {
        $user = Helper_User::getUser();

        $article = Doctrine::instance()->getRepository('Model_Article')->findOneBy(array('id' => $articleId));
        $favorite = Doctrine::instance()->getRepository('Model_Favorite')->findOneByArticle($article);

        if($groupId !== null) {
            $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $groupId));

            if(!$favorite) {
                // for own articles
                $favorite = $article->addToFavorites();
            }

            if($user && $group && $article) {
                if(!$group->favorites->contains($favorite)) {
                    $group->favorites->add($favorite);
                    $favorite->favoriteGroups->add($group);
                    Doctrine::instance()->persist($favorite);
                    Doctrine::instance()->persist($group);
                    Doctrine::instance()->flush();
                }
            }
        } else {
            foreach($user->favoritegroups as $group) {
                if($group->favorites->contains($favorite)) {
                    $group->favorites->removeElement($favorite);
                }
            }
        }
    }

    /**
     * @param $groupId
     * @return bool
     */
    static function deleteArticleGroup($groupId) {
        $user = Helper_User::getUser();
        $group = Doctrine::instance()->getRepository('Model_Favoritegroup')->findOneBy(array('user' => $user->id, 'id' => $groupId));
        if($group) {
            Doctrine::instance()->remove($group);
            Doctrine::instance()->flush();
            return true;
        }
        return false;
    }
}