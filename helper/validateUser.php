<?php

function validateRegister($user)
{

    $errors = array();
    $email = '';
    if (empty($user['email'])) {
        array_push($errors, 'Email is required');
    } else {
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "Invalid email format");
        } else {
            $email = $user['email'];
        }
    }
    if (empty($user['password'])) {
        array_push($errors, 'Password is required');
    } elseif (strlen($user['password']) < 6) {
        array_push($errors, 'Password must have more than 6 characters');
    }
    $existingUser = selectOne('user', ['email' => $email]);

    if (isset($existingUser)) {
        array_push($errors, 'Email is already registered ');
    }

    return $errors;
}

function validateChangePass($user)
{

    $errors = array();
    $id = $_SESSION['user']['ID'];
    $pass = custom("SELECT user.password FROM user where ID = $id");
    if (!password_verify($user['password'], $pass[0])) {
        array_push($errors, 'Wrong password');
    }
    return $errors;
}
function validateLogin($user)
{

    $errors = array();

    if (empty($user['email']) || !isset($user['email'])) {
        array_push($errors, 'Email is required');
    } else {
        if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            array_push($errors, "Invalid email format");
        }
    }
    if (empty($user['password']) || !isset($user['password'])) {
        array_push($errors, 'Password is required');
    }


    return $errors;
}