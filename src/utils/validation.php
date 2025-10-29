<?php

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    return strlen($password) >= 6;
}

function validateTicketTitle($title) {
    if (empty(trim($title))) {
        return 'Title is required';
    }
    if (strlen($title) < 3) {
        return 'Title must be at least 3 characters';
    }
    if (strlen($title) > 150) {
        return 'Title must be less than 150 characters';
    }
    return null;
}

function validateTicketDescription($description) {
    if ($description && strlen($description) > 1000) {
        return 'Description must be less than 1000 characters';
    }
    return null;
}