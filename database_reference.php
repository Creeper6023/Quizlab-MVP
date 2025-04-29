<?php
require_once 'config.php';
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container">
    <h1 class="mb-4"><i class="fas fa-database me-2"></i>Database Reference Guide</h1>
    
    <div class="alert alert-info mb-4">
        <i class="fas fa-info-circle me-2"></i>
        This is a reference guide for the QuizLabs database structure and navigation paths.
    </div>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Database Tables and Structure</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Table Name</th>
                                    <th>Description</th>
                                    <th>Key Fields</th>
                                    <th>Related Tables</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><code>users</code></td>
                                    <td>Stores user account information</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>username</code> - Unique username</li>
                                            <li><code>password</code> - Hashed password</li>
                                            <li><code>role</code> - User role (admin, teacher, student)</li>
                                            <li><code>hash_id</code> - Unique hash identifier</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (created_by)</li>
                                            <li><code>classes</code> (created_by)</li>
                                            <li><code>quiz_attempts</code> (student_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>quizzes</code></td>
                                    <td>Stores quiz information</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>title</code> - Quiz title</li>
                                            <li><code>description</code> - Quiz description</li>
                                            <li><code>created_by</code> - User ID of creator</li>
                                            <li><code>is_published</code> - Publication status (0/1)</li>
                                            <li><code>allow_redo</code> - Allow retakes (0/1)</li>
                                            <li><code>hash_id</code> - Unique hash identifier</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>users</code> (created_by)</li>
                                            <li><code>questions</code> (quiz_id)</li>
                                            <li><code>class_quizzes</code> (quiz_id)</li>
                                            <li><code>quiz_attempts</code> (quiz_id)</li>
                                            <li><code>quiz_shares</code> (quiz_id)</li>
                                            <li><code>quiz_retakes</code> (quiz_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>questions</code></td>
                                    <td>Stores quiz questions</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>quiz_id</code> - Associated quiz</li>
                                            <li><code>question_text</code> - Question content</li>
                                            <li><code>model_answer</code> - Model answer for grading</li>
                                            <li><code>points</code> - Maximum points for the question</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                            <li><code>student_answers</code> (question_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>quiz_student_access</code></td>
                                    <td>Junction table for student access to quizzes</td>
                                    <td>
                                        <ul>
                                            <li><code>quiz_id</code> - Associated quiz</li>
                                            <li><code>student_id</code> - Associated student</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                            <li><code>users</code> (student_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>quiz_attempts</code></td>
                                    <td>Tracks student quiz attempts</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>quiz_id</code> - Associated quiz</li>
                                            <li><code>student_id</code> - Student taking the quiz</li>
                                            <li><code>start_time</code> - When attempt started</li>
                                            <li><code>end_time</code> - When attempt finished</li>
                                            <li><code>total_score</code> - Overall score</li>
                                            <li><code>status</code> - Attempt status (in_progress, completed, graded)</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                            <li><code>users</code> (student_id)</li>
                                            <li><code>student_answers</code> (attempt_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>student_answers</code></td>
                                    <td>Stores student responses to questions</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>attempt_id</code> - Associated quiz attempt</li>
                                            <li><code>question_id</code> - Associated question</li>
                                            <li><code>answer_text</code> - Student's answer</li>
                                            <li><code>score</code> - Points awarded</li>
                                            <li><code>feedback</code> - AI feedback on answer</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quiz_attempts</code> (attempt_id)</li>
                                            <li><code>questions</code> (question_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>classes</code></td>
                                    <td>Stores class information</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>name</code> - Class name</li>
                                            <li><code>description</code> - Class description</li>
                                            <li><code>created_by</code> - Teacher who created the class</li>
                                            <li><code>hash_id</code> - Unique hash identifier</li>
                                            <li><code>updated_at</code> - Last update timestamp</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>users</code> (created_by)</li>
                                            <li><code>class_enrollments</code> (class_id)</li>
                                            <li><code>class_quizzes</code> (class_id)</li>
                                            <li><code>class_teachers</code> (class_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>class_enrollments</code></td>
                                    <td>Junction table for student class enrollment</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>class_id</code> - Associated class</li>
                                            <li><code>student_id</code> - Enrolled student</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>classes</code> (class_id)</li>
                                            <li><code>users</code> (student_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>class_quizzes</code></td>
                                    <td>Junction table for quizzes assigned to classes</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>class_id</code> - Associated class</li>
                                            <li><code>quiz_id</code> - Assigned quiz</li>
                                            <li><code>due_date</code> - Optional due date</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>classes</code> (class_id)</li>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>class_teachers</code></td>
                                    <td>Junction table for co-teachers in classes</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>class_id</code> - Associated class</li>
                                            <li><code>teacher_id</code> - Co-teacher</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>classes</code> (class_id)</li>
                                            <li><code>users</code> (teacher_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>quiz_shares</code></td>
                                    <td>Records shared quizzes between users</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>quiz_id</code> - Shared quiz</li>
                                            <li><code>shared_by_id</code> - User sharing the quiz</li>
                                            <li><code>shared_with_id</code> - User receiving access</li>
                                            <li><code>permission_level</code> - Access level (view, edit, full)</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                            <li><code>users</code> (shared_by_id, shared_with_id)</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><code>quiz_retakes</code></td>
                                    <td>Tracks permissions for quiz retakes</td>
                                    <td>
                                        <ul>
                                            <li><code>id</code> - Primary key</li>
                                            <li><code>quiz_id</code> - Quiz to retake</li>
                                            <li><code>student_id</code> - Student granted retake</li>
                                            <li><code>granted_by</code> - Teacher who granted permission</li>
                                            <li><code>used</code> - Whether retake was used (0/1)</li>
                                        </ul>
                                    </td>
                                    <td>
                                        <ul>
                                            <li><code>quizzes</code> (quiz_id)</li>
                                            <li><code>users</code> (student_id, granted_by)</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">User Roles and Permissions</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Role</th>
                                    <th>Description</th>
                                    <th>Permissions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Admin</strong></td>
                                    <td>System administrator with full access</td>
                                    <td>
                                        <ul>
                                            <li>Create/edit/delete all users</li>
                                            <li>Create/edit/delete all classes</li>
                                            <li>Create/edit/delete all quizzes</li>
                                            <li>View all student attempts and results</li>
                                            <li>Configure system settings</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Teacher</strong></td>
                                    <td>Educator who creates and manages quizzes</td>
                                    <td>
                                        <ul>
                                            <li>Create/edit/delete own classes</li>
                                            <li>Create/edit/delete own quizzes</li>
                                            <li>Share quizzes with other teachers</li>
                                            <li>Add students to classes</li>
                                            <li>View results for own students</li>
                                            <li>Grant quiz retakes to students</li>
                                        </ul>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Student</strong></td>
                                    <td>User who takes quizzes and views results</td>
                                    <td>
                                        <ul>
                                            <li>Take assigned quizzes</li>
                                            <li>View own quiz results</li>
                                            <li>Retake quizzes if allowed</li>
                                        </ul>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">Navigation and URL Paths</h3>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light">
                                <tr>
                                    <th>Page Purpose</th>
                                    <th>URL Path</th>
                                    <th>Access Roles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="3" class="bg-light"><strong>Admin Section</strong></td>
                                </tr>
                                <tr>
                                    <td>Admin Dashboard</td>
                                    <td><code>/admin/index.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Manage Classes</td>
                                    <td><code>/admin/classes/index.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Create Class</td>
                                    <td><code>/admin/classes/create_class.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Manage Class</td>
                                    <td><code>/admin/classes/manage.php?id=[class_id]</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Manage Quizzes</td>
                                    <td><code>/admin/quizzes/index.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Create Quiz</td>
                                    <td><code>/admin/quizzes/create_quiz.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Edit Quiz</td>
                                    <td><code>/admin/quizzes/edit_quiz.php?id=[quiz_id]</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Manage Users</td>
                                    <td><code>/admin/users/index.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Add User</td>
                                    <td><code>/admin/users/add_user.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>Edit User</td>
                                    <td><code>/admin/users/edit_user.php?id=[user_id]</code></td>
                                    <td>Admin</td>
                                </tr>
                                <tr>
                                    <td>System Settings</td>
                                    <td><code>/admin/settings.php</code></td>
                                    <td>Admin</td>
                                </tr>
                                
                                <tr>
                                    <td colspan="3" class="bg-light"><strong>Teacher Section</strong></td>
                                </tr>
                                <tr>
                                    <td>Teacher Dashboard</td>
                                    <td><code>/teacher/index.php</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Manage Classes</td>
                                    <td><code>/teacher/classes/index.php</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Create Class</td>
                                    <td><code>/teacher/classes/create_class.php</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Manage Class</td>
                                    <td><code>/teacher/classes/manage.php?id=[class_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Edit Quiz</td>
                                    <td><code>/teacher/edit_quiz.php?id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>View Quiz</td>
                                    <td><code>/teacher/view_quiz.php?id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Publish Quiz</td>
                                    <td><code>/teacher/publish_quiz.php?id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Unpublish Quiz</td>
                                    <td><code>/teacher/unpublish_quiz.php?id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Share Quiz</td>
                                    <td><code>/teacher/share_quiz.php?id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>Allow Quiz Retake</td>
                                    <td><code>/teacher/allow_retake.php?quiz_id=[quiz_id]&student_id=[student_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                <tr>
                                    <td>View Quiz Results</td>
                                    <td><code>/teacher/view_results.php?quiz_id=[quiz_id]</code></td>
                                    <td>Teacher</td>
                                </tr>
                                
                                <tr>
                                    <td colspan="3" class="bg-light"><strong>Student Section</strong></td>
                                </tr>
                                <tr>
                                    <td>Student Dashboard</td>
                                    <td><code>/student/index.php</code></td>
                                    <td>Student</td>
                                </tr>
                                <tr>
                                    <td>Take Quiz</td>
                                    <td><code>/student/take_quiz.php?id=[quiz_id]</code></td>
                                    <td>Student</td>
                                </tr>
                                <tr>
                                    <td>View Quiz Results</td>
                                    <td><code>/student/view_result.php?attempt_id=[attempt_id]</code></td>
                                    <td>Student</td>
                                </tr>
                                
                                <tr>
                                    <td colspan="3" class="bg-light"><strong>Common Pages</strong></td>
                                </tr>
                                <tr>
                                    <td>Login</td>
                                    <td><code>/auth/login.php</code></td>
                                    <td>All (unauthenticated)</td>
                                </tr>
                                <tr>
                                    <td>Logout</td>
                                    <td><code>/auth/logout.php</code></td>
                                    <td>All (authenticated)</td>
                                </tr>
                                <tr>
                                    <td>User Profile</td>
                                    <td><code>/user/profile.php</code></td>
                                    <td>All (authenticated)</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once INCLUDES_PATH . '/footer.php'; ?>