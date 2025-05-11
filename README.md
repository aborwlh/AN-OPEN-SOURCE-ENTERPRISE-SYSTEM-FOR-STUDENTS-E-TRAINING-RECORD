# Ocean E-Training Platform

## Overview

Ocean E-Training is a comprehensive online learning management system designed to connect students with expert instructors. The platform offers a wide range of courses across various categories, allowing students to learn at their own pace and earn certificates upon completion.

## Features

### For Students
- **Course Enrollment**: Browse and enroll in courses from various categories
- **Learning Dashboard**: Track progress across enrolled courses
- **Course Materials**: Access various types of learning materials (text, files, URLs)
- **Certificates**: Earn verifiable certificates upon course completion
- **Progress Tracking**: Monitor your learning journey with detailed progress statistics
- **Feedback System**: Rate and review courses you've completed

### For Instructors
- **Course Management**: Create, edit, and manage your courses
- **Student Management**: View and manage enrolled students
- **Content Creation**: Upload various types of learning materials
- **Analytics**: Track student engagement and course performance
- **Certificate Management**: Issue certificates to students who complete courses

### For Administrators
- **User Management**: Manage students, instructors, and other administrators
- **Course Oversight**: Monitor and manage all courses on the platform
- **Category Management**: Create and manage course categories
- **Platform Statistics**: Access comprehensive analytics about platform usage
- **System Configuration**: Manage system settings and configurations

### General Features
- **Responsive Design**: Access the platform from any device (desktop, tablet, mobile)
- **Multi-language Support**: Platform supports both English and Arabic languages
- **Certificate Verification**: Public verification system for issued certificates
- **User Profiles**: Customizable user profiles with personal information

## Technical Details

### Technologies Used
- **Backend**: PHP with MySQL database
- **Frontend**: HTML5, CSS3, JavaScript
- **Frameworks/Libraries**: Bootstrap, Font Awesome
- **Additional Tools**: AJAX for dynamic content loading

### System Requirements
- PHP 7.2 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## Installation

1. **Clone the repository**
   \`\`\`
   git clone https://github.com/aborwlh/AN-OPEN-SOURCE-ENTERPRISE-SYSTEM-FOR-STUDENTS-E-TRAINING-RECORD.git
   \`\`\`

2. **Database Setup**
   - Create a new MySQL database
   - Import the database schema from `schema.sql`

3. **Configuration**
   - Update database connection details in `config.php`
   - Configure other settings as needed

4. **Web Server Configuration**
   - Point your web server to the project's root directory
   - Ensure proper permissions for file uploads (assets/course_materials, assets/images)

5. **First Login**
   - Default admin credentials:
     create it from the data base then login 

## Usage

### Student Journey
1. Register for an account
2. Browse available courses
3. Enroll in desired courses
4. Access course materials and complete learning activities
5. Track progress through the student dashboard
6. Receive a certificate upon course completion
7. Provide feedback on completed courses

### Instructor Journey
1. Register and apply for instructor status (or be assigned by admin)
2. Create new courses with detailed information
3. Upload course materials (text, files, URLs)
4. Monitor student enrollments and progress
5. Interact with students through the platform
6. Review and approve certificate issuance

### Administrator Journey
1. Manage users (students, instructors, admins)
2. Oversee course creation and management
3. Create and manage course categories
4. Monitor platform statistics and performance
5. Configure system settings

## Customization

### Themes and Styling
- Modify CSS in `assets/style/styles.css` for global styling
- Individual page styles are included within the respective PHP files

### Language Support
- Edit language strings in the `translate()` function in `config.php`
- Add new languages by extending the translation array

### System Configuration
- Most system settings can be configured in the admin dashboard
- Advanced configurations can be modified in `config.php`

## Security Considerations

- All user passwords are hashed using secure methods
- Input validation is implemented to prevent SQL injection
- Session management includes protection against session hijacking
- File uploads are validated for type and size to prevent security issues
- Certificate verification system uses unique codes to prevent forgery

## Support and Contribution

For support requests, bug reports, or feature suggestions, please contact:
- Email: oceanproject097@gmail.com
- Phone: +966550381788


## Acknowledgements

- Special thanks to all contributors who have helped develop this platform
- Icons provided by Font Awesome
- UI components based on Bootstrap framework
