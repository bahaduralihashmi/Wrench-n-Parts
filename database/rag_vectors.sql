-- ============================================
-- ADVANCED RAG: VECTOR EMBEDDINGS + SESSION STATE
-- ============================================

-- Stores precomputed Gemini embedding vectors for every knowledge base entity
CREATE TABLE IF NOT EXISTS kb_embeddings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_type ENUM('problem','article','dtc','faq') NOT NULL,
    source_id INT NOT NULL,
    label VARCHAR(255) NOT NULL,
    embedding MEDIUMTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_src (source_type, source_id)
) ENGINE=InnoDB;

-- Multi-turn conversation state (collected vehicle info per session)
CREATE TABLE IF NOT EXISTS chatbot_state (
    session_id VARCHAR(64) NOT NULL PRIMARY KEY,
    state JSON NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;