<?php


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