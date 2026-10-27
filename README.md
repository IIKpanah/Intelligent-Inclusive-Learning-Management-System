**Contact iikpanah2018@gmail.com for more projects!***
# IILMS – Intelligent Inclusive Learning Management System


## Installation
1. Clone this repository.
2. Run `composer install` to install dependencies (vlucas/phpdotenv).
3. Create a MySQL database and import the schema (provided in `database/schema.sql`).
4. Copy `.env.example` to `.env` and fill in your credentials.
5. Ensure the `uploads/` directory and its subdirectories are writable.
6. Set the document root to the project folder.
7. Configure your web server (Apache/Nginx) to serve PHP.

## Features
- Disability‑aware authentication
- Role‑based dashboards (admin, lecturer, student)
- AI‑powered tutor with adaptation
- Automatic content conversion (PDF → audio, braille, simplified text, flashcards)
- Offline support with Service Worker
- Voice and video submission for assignments

## Dependencies
- PHP 7.4+
- MySQL
- Composer
- OpenAI API key (for AI)
- Azure Speech Services (for TTS) – optional
- Tesseract OCR (for scanned PDFs)

## Usage
- Access the site via `BASE_URL`.
- Register with your disability type and preferences.
- Log in and enjoy a personalized learning experience.

## Contributing
Pull requests are welcome.

