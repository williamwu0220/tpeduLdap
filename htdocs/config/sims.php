<?php

return [
    'ps' => [
        'oauth_id' => env('PS_CLIENT_ID'),
        'oauth_secret' => urlencode(env('PS_CLIENT_SECRET')),
        'oauth_ip' => env('PS_SERVICE_IP'),
        'resource' => [
            'school_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/public/{sid}/schoolinfo/schoolinfo',
            'department_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/public/{sid}/schoolinfo/departmentinfo',
            'classes_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/public/{sid}/schoolinfo/clsinfo',
            'special_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/public/{sid}/schoolinfo/specilinfo',
            'calendar_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/public/{sid}/schoolinfo/calendarinfo',
            'student_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/student/{stdno}',
            'students_in_class' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/class/{clsid}',
            'leaders_in_class' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/leaders/{seme}/{clsid}',
            'student_subjects_score' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/subjectscore/{seme}/{stdno}',
            'student_domains_score' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/stdlibscore/{seme}/{stdno}',
            'student_attendance_record' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/absdat/{seme}/{stdno}',
            'student_health_record' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/students/health/{seme}/{stdno}',
            'student_parents_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/parents/student/{stdno}',
            'teacher_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/teachers/teacher/{teaid}',
            'teachers_in_class' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/teachers/class/{clsid}',
            'teacher_schedule' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/teachers/class/{teaid}',
            'teacher_tutor_students' => 'https://eschool-restful.tp.edu.tw/dataapi_web/user/{sid}/teachers/student/{teaid}',
            'subject_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/subjects/subject/{subjid}',
            'subject_for_class' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/subjects/class/{clsid}',
            'subject_assign_to_teacher' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/subjects/teacher/{teaid}',
            'classses_by_grade' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/classes/grade/{grade}',
            'classs_schedule' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/classes/class/{clsid}',
            'classs_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/classes/classinfo/{clsid}',
            'library_books' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/libraryinfo/bookinfo',
            'class_lend_record' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/libraryinfo/yearlendtot/{seme}/{clsid}',
            'book_info' => 'https://eschool-restful.tp.edu.tw/dataapi_web/admin/{sid}/libraryinfo/bookisbn/{isbn}',
        ],
    ],
    'js' => [
        'oauth_id' => env('JS_CLIENT_ID'),
        'oauth_secret' => env('JS_CLIENT_SECRET'),
        'oauth_ip' => env('JS_SERVICE_IP'),
    ],
];