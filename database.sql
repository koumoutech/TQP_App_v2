-- Structure de la table users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    service VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Structure de la table products
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    category_id INT NOT NULL,
    description TEXT,
    details TEXT,
    media_url VARCHAR(255),
    media_type ENUM('image', 'video'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Structure de la table categories
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Structure de la table accounts
CREATE TABLE accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    link VARCHAR(255),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Structure de la table user_accounts
CREATE TABLE user_accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    account_id INT NOT NULL,
    status ENUM('actif', 'bloqué', 'pas de compte') NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (account_id) REFERENCES accounts(id)
);

-- Structure de la table quizzes
CREATE TABLE quizzes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL,
    duration INT NOT NULL, -- en minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Structure de la table questions
CREATE TABLE questions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    quiz_id INT NOT NULL,
    question TEXT NOT NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id)
);

-- Structure de la table answers
CREATE TABLE answers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    answer TEXT NOT NULL,
    is_correct BOOLEAN NOT NULL,
    FOREIGN KEY (question_id) REFERENCES questions(id)
); 