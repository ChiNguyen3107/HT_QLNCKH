<?php
/**
 * Web routes
 */

// Home routes
$router->get('/', 'AuthController@index');
$router->get('/login', 'AuthController@showLogin');
$router->post('/login', 'AuthController@login');
$router->get('/logout', 'AuthController@logout');

// Admin routes
$router->get('/admin', 'AdminController@dashboard', ['AuthMiddleware', 'AdminMiddleware']);
$router->get('/admin/dashboard', 'AdminController@dashboard', ['AuthMiddleware', 'AdminMiddleware']);
$router->get('/admin/users', 'AdminController@users', ['AuthMiddleware', 'AdminMiddleware']);
$router->get('/admin/projects', 'AdminController@projects', ['AuthMiddleware', 'AdminMiddleware']);

// Student routes
$router->get('/student', 'StudentController@dashboard', ['AuthMiddleware']);
$router->get('/student/dashboard', 'StudentController@dashboard', ['AuthMiddleware']);
$router->get('/student/projects', 'StudentController@projects', ['AuthMiddleware']);
$router->get('/student/profile', 'StudentController@profile', ['AuthMiddleware']);

// Teacher routes
$router->get('/teacher', 'TeacherController@dashboard', ['AuthMiddleware']);
$router->get('/teacher/dashboard', 'TeacherController@dashboard', ['AuthMiddleware']);
$router->get('/teacher/projects', 'TeacherController@projects', ['AuthMiddleware']);
$router->get('/teacher/students', 'TeacherController@students', ['AuthMiddleware']);

// Research routes
$router->get('/research', 'ResearchController@dashboard', ['AuthMiddleware']);
$router->get('/research/dashboard', 'ResearchController@dashboard', ['AuthMiddleware']);
$router->get('/research/projects', 'ResearchController@projects', ['AuthMiddleware']);
$router->get('/research/evaluations', 'ResearchController@evaluations', ['AuthMiddleware']);

// API routes
$router->get('/api/v1/projects', 'ResearchController@apiProjects');
$router->get('/api/v1/students', 'StudentController@apiStudents');
$router->get('/api/v1/teachers', 'TeacherController@apiTeachers');

