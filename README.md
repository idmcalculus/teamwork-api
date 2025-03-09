# Teamwork API

## About Teamwork API

Teamwork API is a backend application built with Laravel that facilitates interaction among employees within an organization. It provides a platform for employees to share articles, GIFs, and other media, as well as comment on posts from colleagues.

## Features

- **User Authentication**: Secure registration and login using Laravel Sanctum for API token authentication.
- **User Management**: Create and manage user profiles with details like department, job role, and contact information.
- **Posts Management**: Create, read, update, and delete posts (articles or GIFs).
- **Comments System**: Add, edit, and delete comments on posts.
- **Admin Functionality**: Special privileges for administrators to manage content and users.
- **Content Flagging**: Ability to flag inappropriate content for review.

## Tech Stack

- **PHP 8.x**
- **Laravel 10.x**
- **MySQL/SQLite** (Database)
- **Laravel Sanctum** (API Authentication)

## Installation

1. Clone the repository:
   ```bash
   git clone <repository-url>
   cd teamwork-api
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Set up environment variables:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in the `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=teamwork
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Create a symbolic link for storage:
   ```bash
   php artisan storage:link
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## API Endpoints

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Login and get access token
- `POST /api/logout` - Logout and invalidate token (requires authentication)
- `GET /api/user` - Get authenticated user details (requires authentication)

### Users
- `GET /api/users` - Get all users (requires authentication)
- `GET /api/users/{id}` - Get a specific user (requires authentication)
- `PUT /api/profile` - Update authenticated user's profile (requires authentication)
- `POST /api/change-password` - Change authenticated user's password (requires authentication)
- `PUT /api/users/{id}/admin-status` - Update user's admin status (requires admin privileges)

### Posts
- `GET /api/posts` - Get all posts (requires authentication)
- `POST /api/posts` - Create a new post (requires authentication)
- `GET /api/posts/{id}` - Get a specific post (requires authentication)
- `PUT /api/posts/{id}` - Update a post (requires authentication, owner or admin only)
- `DELETE /api/posts/{id}` - Delete a post (requires authentication, owner or admin only)
- `PUT /api/posts/{id}/flag` - Flag a post as inappropriate (requires authentication)
- `GET /api/users/{userId}/posts` - Get all posts by a specific user (requires authentication)

### Comments
- `GET /api/posts/{postId}/comments` - Get all comments for a post (requires authentication)
- `POST /api/posts/{postId}/comments` - Add a comment to a post (requires authentication)
- `GET /api/posts/{postId}/comments/{id}` - Get a specific comment (requires authentication)
- `PUT /api/posts/{postId}/comments/{id}` - Update a comment (requires authentication, owner only)
- `DELETE /api/posts/{postId}/comments/{id}` - Delete a comment (requires authentication, owner, post owner, or admin only)
- `GET /api/users/{userId}/comments` - Get all comments by a specific user (requires authentication)

## Database Schema

### Users Table
- `id` - Primary key
- `name` - User's full name
- `email` - User's email address (unique)
- `password` - Hashed password
- `department` - User's department
- `job_role` - User's job role
- `avatar` - URL to user's profile image
- `bio` - User's biography
- `address` - User's address
- `gender` - User's gender
- `phone` - User's phone number
- `is_admin` - Boolean flag for admin status
- Timestamps

### Posts Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `title` - Post title
- `content` - Post content
- `image_url` - URL to post image (for GIFs)
- `type` - Post type (article or gif)
- `flagged` - Boolean flag for inappropriate content
- Soft delete and timestamps

### Comments Table
- `id` - Primary key
- `user_id` - Foreign key to users table
- `post_id` - Foreign key to posts table
- `comment` - Comment text
- Soft delete and timestamps

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
