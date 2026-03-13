-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 108.181.92.77    Database: mp00155232000119
-- ------------------------------------------------------
-- Server version	8.0.45-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `causa`
--

DROP TABLE IF EXISTS `causa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `causa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `situacao` tinyint(1) NOT NULL DEFAULT '1',
  `grupo_causa_id` bigint unsigned NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_causa_grupo` (`grupo_causa_id`),
  CONSTRAINT `fk_causa_grupo` FOREIGN KEY (`grupo_causa_id`) REFERENCES `grupo_causa` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `causa`
--

LOCK TABLES `causa` WRITE;
/*!40000 ALTER TABLE `causa` DISABLE KEYS */;
/*!40000 ALTER TABLE `causa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `femea`
--

DROP TABLE IF EXISTS `femea`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `femea` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_primaria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_secundaria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_compra` enum('leitoa','matriz_vazia','matriz_gestante') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_compra` date NOT NULL,
  `ciclos_ate_compra` int unsigned DEFAULT NULL,
  `data_cobertura` date DEFAULT NULL,
  `raca_id` bigint unsigned NOT NULL,
  `valor_compra` decimal(10,2) DEFAULT NULL,
  `peso_compra` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `caracteristicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_femea_id_primaria` (`id_primaria`),
  UNIQUE KEY `uq_femea_id_secundaria` (`id_secundaria`),
  KEY `idx_femea_data_compra` (`data_compra`),
  KEY `idx_femea_raca` (`raca_id`),
  KEY `idx_femea_fornecedor` (`fornecedor_id`),
  KEY `idx_femea_tipo_compra` (`tipo_compra`),
  CONSTRAINT `fk_femea_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_femea_raca` FOREIGN KEY (`raca_id`) REFERENCES `raca` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `femea`
--

LOCK TABLES `femea` WRITE;
/*!40000 ALTER TABLE `femea` DISABLE KEYS */;
/*!40000 ALTER TABLE `femea` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `femea_movimento`
--

DROP TABLE IF EXISTS `femea_movimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `femea_movimento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `femea_id` bigint unsigned NOT NULL,
  `acao` enum('compra','morte','descarte','venda') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  `causa_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_femea_movimento_fornecedor` (`fornecedor_id`),
  KEY `idx_femea_movimento_femea_data` (`femea_id`,`data`),
  KEY `idx_femea_movimento_acao` (`acao`),
  KEY `idx_femea_movimento_causa_id` (`causa_id`),
  CONSTRAINT `fk_femea_movimento_causa` FOREIGN KEY (`causa_id`) REFERENCES `causa` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_femea_movimento_femea` FOREIGN KEY (`femea_id`) REFERENCES `femea` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_femea_movimento_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `femea_movimento`
--

LOCK TABLES `femea_movimento` WRITE;
/*!40000 ALTER TABLE `femea_movimento` DISABLE KEYS */;
/*!40000 ALTER TABLE `femea_movimento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fornecedor`
--

DROP TABLE IF EXISTS `fornecedor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fornecedor` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fornecedor`
--

LOCK TABLES `fornecedor` WRITE;
/*!40000 ALTER TABLE `fornecedor` DISABLE KEYS */;
/*!40000 ALTER TABLE `fornecedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_causa`
--

DROP TABLE IF EXISTS `grupo_causa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_causa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_causa`
--

LOCK TABLES `grupo_causa` WRITE;
/*!40000 ALTER TABLE `grupo_causa` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupo_causa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `macho`
--

DROP TABLE IF EXISTS `macho`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `macho` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `id_primaria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_secundaria` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_compra` date NOT NULL,
  `raca_id` bigint unsigned NOT NULL,
  `valor_compra` decimal(10,2) DEFAULT NULL,
  `peso_compra` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `caracteristicas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `localizacao` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_macho_id_primaria` (`id_primaria`),
  UNIQUE KEY `uq_macho_id_secundaria` (`id_secundaria`),
  KEY `idx_macho_data_compra` (`data_compra`),
  KEY `idx_macho_raca` (`raca_id`),
  KEY `idx_macho_fornecedor` (`fornecedor_id`),
  CONSTRAINT `fk_macho_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_macho_raca` FOREIGN KEY (`raca_id`) REFERENCES `raca` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `macho`
--

LOCK TABLES `macho` WRITE;
/*!40000 ALTER TABLE `macho` DISABLE KEYS */;
/*!40000 ALTER TABLE `macho` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `macho_movimento`
--

DROP TABLE IF EXISTS `macho_movimento`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `macho_movimento` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `macho_id` bigint unsigned NOT NULL,
  `acao` enum('compra','morte','descarte','venda') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `causa_id` bigint unsigned DEFAULT NULL,
  `comprador` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_macho_movimento_fornecedor` (`fornecedor_id`),
  KEY `idx_macho_movimento_macho_data` (`macho_id`,`data`),
  KEY `idx_macho_movimento_acao` (`acao`),
  KEY `idx_macho_movimento_causa_id` (`causa_id`),
  CONSTRAINT `fk_macho_movimento_causa` FOREIGN KEY (`causa_id`) REFERENCES `causa` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_macho_movimento_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_macho_movimento_macho` FOREIGN KEY (`macho_id`) REFERENCES `macho` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `macho_movimento`
--

LOCK TABLES `macho_movimento` WRITE;
/*!40000 ALTER TABLE `macho_movimento` DISABLE KEYS */;
/*!40000 ALTER TABLE `macho_movimento` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meta`
--

DROP TABLE IF EXISTS `meta`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `meta` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `chave` varchar(120) NOT NULL,
  `valor` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meta`
--

LOCK TABLES `meta` WRITE;
/*!40000 ALTER TABLE `meta` DISABLE KEYS */;
/*!40000 ALTER TABLE `meta` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `raca`
--

DROP TABLE IF EXISTS `raca`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `raca` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `raca`
--

LOCK TABLES `raca` WRITE;
/*!40000 ALTER TABLE `raca` DISABLE KEYS */;
/*!40000 ALTER TABLE `raca` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `racao`
--

DROP TABLE IF EXISTS `racao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `racao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `classificacao` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `fase_animal` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `proteina_bruta` decimal(6,2) DEFAULT NULL,
  `energia_metabolizavel` decimal(10,2) DEFAULT NULL,
  `fibra` decimal(6,2) DEFAULT NULL,
  `lisina` decimal(6,2) DEFAULT NULL,
  `calcio` decimal(6,2) DEFAULT NULL,
  `fosforo` decimal(6,2) DEFAULT NULL,
  `marca` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custo_por_kg` decimal(10,2) DEFAULT NULL,
  `unidade_compra` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `peso_embalagem` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `tipo_racao_id` bigint unsigned NOT NULL,
  `estoque` decimal(10,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `fk_racao_fornecedor` (`fornecedor_id`),
  KEY `fk_racao_tipo_racao` (`tipo_racao_id`),
  CONSTRAINT `fk_racao_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedor` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_racao_tipo_racao` FOREIGN KEY (`tipo_racao_id`) REFERENCES `tipo_racao` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `racao`
--

LOCK TABLES `racao` WRITE;
/*!40000 ALTER TABLE `racao` DISABLE KEYS */;
/*!40000 ALTER TABLE `racao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipo_racao`
--

DROP TABLE IF EXISTS `tipo_racao`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipo_racao` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nome` (`nome`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipo_racao`
--

LOCK TABLES `tipo_racao` WRITE;
/*!40000 ALTER TABLE `tipo_racao` DISABLE KEYS */;
/*!40000 ALTER TABLE `tipo_racao` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuario`
--

DROP TABLE IF EXISTS `usuario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verificado_em` timestamp NULL DEFAULT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('consultor','operador','administrador') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lembrete_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  `foto_perfil` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (1,'Administrador','admin@mastersui.com.br',NULL,'$2y$12$wZkKaRiQjUqme24lyopEDO8YUEKSF0uQWhBBWggQ361b0j9ot49IG','00000000000000','admin','administrador','T0vGamDZwWfqyDVBxgMAHCTkzrWzeQa0QQfW2KtVS7aAbcWVkh2X1Bggait4','2026-03-12 00:29:47','2026-03-12 00:29:47',NULL);
/*!40000 ALTER TABLE `usuario` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-11 21:54:41
