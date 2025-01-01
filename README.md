# File System API

## 📖 Overview

The **File System API** provides a robust and scalable solution for managing nested folders and files. With user-specific permissions, secure access, and the ability to download nested structures as ZIP archives, this API is ideal for building advanced file management systems.

---

## 🚀 Features

### 🖂 Folder & File Management
- Create, update, delete, and search files and folders.
- Manage nested folder structures efficiently using the **Nested Set Model**.

### 🔐 User Permissions
- Fine-grained permissions:
  - `read`, `write`, `full_access`, `private`
- Explicit file permissions or inherited permissions from parent folders.
- Secure role-based access for files and folders.

### 📦 File Download
- Generate **signed download links** for secure access.
- Download folders (and their nested contents) as a ZIP archive.

### 🛡️ Security & Authentication
- Role-based access control.
- Signed URLs for temporary download access.
- Authentication middleware for secure endpoints.

---

## 📋 Prerequisites

- PHP >= 8.0
- Laravel >= 9.x
- Composer
- MySQL (or compatible database)
- Node.js and npm (for optional frontend development)

---

## ⚙️ Installation

### 1️⃣ Clone the Repository
```bash
git clone https://github.com/AmjadAlSyrafi/File-Sys-api.git
cd File-Sys-api
```

### 2️⃣ Install Dependencies
```bash
composer install
```

### 3️⃣ Environment Setup
```bash
cp .env.example .env
```
- Update your `.env` file with the necessary database credentials and storage configurations.

### 4️⃣ Generate Application Key
```bash
php artisan key:generate
```

### 5️⃣ Migrate and Seed the Database
```bash
php artisan migrate --seed
```

### 6️⃣ Create Storage Links
```bash
php artisan storage:link
```

### 7️⃣ Start the Server
```bash
php artisan serve
```

---

## 🌐 API Endpoints

### Authentication
- `POST /api/login` - User login.
- `POST /api/register` - User registration.

### Folder Management
- `POST /api/folders` - Create a new folder.
- `GET /api/folders` - List accessible root folders.
- `PUT /api/folders/{id}` - Update folder details.
- `DELETE /api/folders/{id}` - Delete a folder.
- `GET /api/folders/{id}/search` - Search within a folder and its descendants.

### File Management
- `POST /api/files` - Upload a file.
- `PUT /api/files/{id}` - Update file details.
- `DELETE /api/files/{id}` - Delete a file.
- `GET /api/download/tmp/{file}` - Download a file or folder.

### Permissions
- `PUT /api/folders/{id}/permissions` - Update folder permissions.
- `PUT /api/files/{id}/permissions` - Update file permissions.

---

### 📦 Folder and File Download

**Download Folders as ZIP Archives:**
- Generate a signed download link:
  ```bash
  GET /api/folders/{id}/download
  ```
- Example Response:
  ```json
  {
    "download_link": "http://127.0.0.1:8000/api/download/tmp/folder_1.zip"
  }
  ```
- Access the signed link to download the ZIP.

**Direct File Download:**
- Access files securely via signed URLs.

---

### 🔄 Deployment

#### Push to GitHub
```bash
git init
git remote add origin https://github.com/AmjadAlSyrafi/File-Sys-api.git
git add .
git commit -m "Initial Commit"
git branch -M main
git push -u origin main
```

#### CI/CD Integration (Optional)
- Use **GitHub Actions** for automated testing and deployment.

---

### 🛠 Contribution

1. Fork the repository.
2. Create a new branch for your feature:
   ```bash
   git checkout -b feature-name
   ```
3. Commit your changes:
   ```bash
   git commit -m "Add feature-name"
   ```
4. Push your branch:
   ```bash
   git push origin feature-name
   ```
5. Open a pull request on GitHub.

---

### 📜 License

This project is licensed under the [MIT License](https://opensource.org/licenses/MIT).

---

## 👤 Author

Developed by [Amjad AlSyrafi](https://github.com/AmjadAlSyrafi).  
Feel free to contribute, raise issues, or fork this repository! 🚀

