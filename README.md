# MicroBoard

MicroBoard is a lightweight, easy-to-install bulletin board system (BBS) built with PHP and MySQL. It supports multiple languages (Korean, English, Japanese, Chinese) and provides essential features for community engagement.


## Requirements

*   **PHP**: 7.4 or higher
*   **MySQL**: 5.7 or higher (or MariaDB equivalent)
*   **Web Server**: Apache, Nginx, or any server capable of running PHP

## Installation

1.  **Download**: Clone or download this repository to your web server's document root or a subdirectory.
    ```bash
    git clone https://github.com/yourusername/microboard.git
    ```
2.  **Permissions**: Ensure the web server has write permissions to the root directory of the project. The installer needs to create a `config.php` file.
    ```bash
    chmod 777 . 
    # OR better yet, give ownership to the web user (e.g., www-data)
    # chown www-data:www-data .
    ```
3.  **Run Installer**: Open your web browser and navigate to the installation page.
    *   Example: `http://yourdomain.com/microboard/install.php`
    *   If you access `index.php` before installation, you will be automatically redirected to `install.php`.
4.  **Configuration**: Fill in the required information on the installation screen:
    *   **Language**: Select your preferred language.
    *   **Database Settings**: Enter your database host, username, password, and the desired database name.
    *   **Admin Settings**: Create an administrator account (username and password).
    *   **License**: Read and agree to the license terms.
5.  **Complete**: Click the "Install" button. Once installation is successful, you will be redirected to the login page.

## Usage

### Login
*   Access the login page (`login.php`) and enter the administrator credentials you created during installation.
*   Standard users can register via `register.php`.

### Dashboard / Board List
*   After logging in, you will see the main board list.
*   The default installation creates a "Free Board" (자유게시판).

### Writing Posts
*   Click on a board to view posts.
*   Click the "Write" button to create a new post.
*   You can edit or delete your own posts. Administrators can manage all posts.

### Administration
*   The admin user has full control over the board.
*   (Future features may include more granular admin panels).

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
