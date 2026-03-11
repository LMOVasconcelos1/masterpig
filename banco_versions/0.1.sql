-- MySQL dump 10.13  Distrib 8.0.45, for Win64 (x86_64)
--
-- Host: 108.181.92.77    Database: mastersui
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
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `causa` VALUES (1,'10','Aborto',1,2,'2026-03-10 12:24:17','2026-03-10 14:12:55'),(2,'4','Abcessos',1,4,'2026-03-10 12:29:09','2026-03-10 12:29:09'),(3,'13','Falsa Prenhez',1,2,'2026-03-10 12:52:57','2026-03-10 18:15:37'),(4,'43','Fora das especificações',1,2,'2026-03-10 12:53:24','2026-03-10 12:53:24'),(5,'5','Acidente',1,5,'2026-03-10 18:15:29','2026-03-10 18:15:29'),(6,'50','Motivo descarte',1,6,'2026-03-10 18:33:46','2026-03-10 18:33:46'),(7,'555','Causa da venda',1,7,'2026-03-10 18:40:51','2026-03-10 18:40:51'),(8,'32','Parto',1,8,'2026-03-10 20:33:50','2026-03-10 20:33:50'),(9,'33','Parto',1,8,'2026-03-10 20:34:10','2026-03-11 01:02:30');
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
  `id_primaria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_secundaria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_compra` enum('leitoa','matriz_vazia','matriz_gestante') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_compra` date NOT NULL,
  `ciclos_ate_compra` int unsigned DEFAULT NULL,
  `data_cobertura` date DEFAULT NULL,
  `raca_id` bigint unsigned NOT NULL,
  `valor_compra` decimal(10,2) DEFAULT NULL,
  `peso_compra` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `caracteristicas` text COLLATE utf8mb4_unicode_ci,
  `localizacao` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
INSERT INTO `femea` VALUES (3,'1001','1001','leitoa','2026-03-10','2026-03-10',NULL,NULL,1,5000.00,60.00,1,NULL,'a','1','2026-03-10 17:50:18','2026-03-10 17:50:18'),(4,'1003','1003','matriz_vazia','2026-01-01','2026-03-01',2,NULL,1,8000.00,85.00,1,NULL,'A','3','2026-03-10 17:54:51','2026-03-10 17:54:51'),(5,'1005','1005','matriz_vazia','2026-01-06','2026-03-10',3,NULL,1,10000.00,90.00,1,NULL,'A','12','2026-03-10 18:04:30','2026-03-10 18:04:30'),(6,'2000','2000','matriz_vazia','2025-12-16','2026-03-10',4,NULL,1,6000.00,80.00,1,NULL,'F','12','2026-03-10 18:10:56','2026-03-10 18:10:56');
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
  `acao` enum('compra','morte','descarte','venda') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
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
INSERT INTO `femea_movimento` VALUES (3,3,'compra','2026-03-10',5000.00,60.00,1,NULL,'2026-03-10 17:50:18','2026-03-10 17:50:18',NULL),(4,4,'compra','2026-03-01',8000.00,85.00,1,NULL,'2026-03-10 17:54:52','2026-03-10 17:54:52',NULL),(5,5,'compra','2026-03-10',10000.00,90.00,1,NULL,'2026-03-10 18:04:30','2026-03-10 18:04:30',NULL),(6,6,'compra','2026-03-10',6000.00,80.00,1,NULL,'2026-03-10 18:10:57','2026-03-10 18:10:57',NULL),(7,4,'morte','2026-03-10',NULL,NULL,NULL,'Acidente',NULL,NULL,5),(8,3,'descarte','2026-03-10',NULL,NULL,NULL,'Motivo descarte',NULL,NULL,6),(9,3,'venda','2026-03-10',3000.00,70.00,NULL,'Causa da venda | Comprador: Miguel',NULL,NULL,7);
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
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `fornecedor` VALUES (1,'Fornecedor Teste','2026-03-10 16:12:35','2026-03-10 16:12:35');
/*!40000 ALTER TABLE `fornecedor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `funcionario`
--

DROP TABLE IF EXISTS `funcionario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `funcionario` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) NOT NULL,
  `usuario` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_funcionario_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `funcionario`
--

LOCK TABLES `funcionario` WRITE;
/*!40000 ALTER TABLE `funcionario` DISABLE KEYS */;
/*!40000 ALTER TABLE `funcionario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupo_causa`
--

DROP TABLE IF EXISTS `grupo_causa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_causa` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `grupo_causa` VALUES (2,'Falhas reprodutivas','2026-03-10 12:20:43','2026-03-10 12:20:43'),(4,'Registro Migrado','2026-03-10 12:28:53','2026-03-10 12:28:53'),(5,'Morte','2026-03-10 18:14:51','2026-03-10 18:14:51'),(6,'Descarte','2026-03-10 18:33:36','2026-03-10 18:33:36'),(7,'Venda','2026-03-10 18:40:47','2026-03-10 18:40:47'),(8,'Nascimento','2026-03-10 20:33:48','2026-03-10 20:33:48'),(9,'TESTE','2026-03-11 01:00:10','2026-03-11 01:00:10');
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
  `id_primaria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_secundaria` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `data_compra` date NOT NULL,
  `raca_id` bigint unsigned NOT NULL,
  `valor_compra` decimal(10,2) DEFAULT NULL,
  `peso_compra` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `caracteristicas` text COLLATE utf8mb4_unicode_ci,
  `localizacao` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baia` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
INSERT INTO `macho` VALUES (1,'3000','3000','2026-01-10','2026-03-10',1,10000.00,120.00,1,NULL,'G','15','2026-03-10 19:01:33','2026-03-10 19:01:33'),(2,'3001','3001','2026-02-10','2026-03-10',1,NULL,NULL,1,NULL,NULL,NULL,'2026-03-10 19:03:22','2026-03-10 19:03:22');
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
  `acao` enum('compra','morte','descarte','venda') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` date NOT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `peso` decimal(10,2) DEFAULT NULL,
  `fornecedor_id` bigint unsigned DEFAULT NULL,
  `causa_id` bigint unsigned DEFAULT NULL,
  `comprador` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `observacoes` text COLLATE utf8mb4_unicode_ci,
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
INSERT INTO `macho_movimento` VALUES (1,1,'compra','2026-03-10',10000.00,120.00,1,NULL,NULL,NULL,'2026-03-10 19:01:33','2026-03-10 19:01:33'),(2,1,'morte','2026-03-10',NULL,NULL,NULL,5,NULL,'Acidente',NULL,NULL),(3,2,'compra','2026-03-10',NULL,NULL,1,NULL,NULL,NULL,'2026-03-10 19:03:23','2026-03-10 19:03:23'),(4,2,'descarte','2026-03-10',NULL,NULL,NULL,6,NULL,'Motivo descarte',NULL,NULL),(5,2,'venda','2026-03-10',9500.00,120.00,NULL,7,'Miguel','Causa da venda',NULL,NULL);
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
  `valor` decimal(10,2) DEFAULT NULL,
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
INSERT INTO `meta` VALUES (1,'meta_plantel_estoque_leitoas',10.00,'2026-03-10 19:58:57','2026-03-11 01:28:45'),(2,'meta_plantel_estoque_matrizes',0.00,'2026-03-10 19:58:57','2026-03-11 01:28:45'),(3,'meta_entrada_peso_leitoa',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(4,'meta_entrada_peso_matriz',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(5,'meta_entrada_peso_macho',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(6,'meta_manutencao_reposicao',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(7,'meta_manutencao_descarte_matrizes',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(8,'meta_manutencao_mortalidade_matrizes',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(9,'meta_manutencao_perdas_leitoas_pre_cobertura',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(10,'meta_selecao_idade_selecao',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(11,'meta_selecao_idade_cobertura',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45'),(12,'meta_produtividade_dias_nao_produtivos',0.00,'2026-03-10 19:59:37','2026-03-11 01:28:45');
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
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `raca` VALUES (1,'Landrace','2026-03-10 17:28:08','2026-03-10 17:28:08');
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
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `classificacao` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fase_animal` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proteina_bruta` decimal(6,2) DEFAULT NULL,
  `energia_metabolizavel` decimal(10,2) DEFAULT NULL,
  `fibra` decimal(6,2) DEFAULT NULL,
  `lisina` decimal(6,2) DEFAULT NULL,
  `calcio` decimal(6,2) DEFAULT NULL,
  `fosforo` decimal(6,2) DEFAULT NULL,
  `marca` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `custo_por_kg` decimal(10,2) DEFAULT NULL,
  `unidade_compra` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
INSERT INTO `racao` VALUES (1,'R001','Ração Crescimento premium','Crescimento','Maternidade',5.00,10.00,15.00,20.00,25.00,30.00,'Marca X',35.00,'40',45.00,'2026-03-10 16:12:49','2026-03-10 16:46:08',1,1,100.00),(2,'R002','NOME','CLASSIFICACAO','TERMINACAO',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-03-10 16:39:58','2026-03-10 16:39:58',NULL,1,50.00);
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
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
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
INSERT INTO `tipo_racao` VALUES (1,'Reprodução','2026-03-10 16:11:38','2026-03-10 16:11:38');
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
  `nome` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verificado_em` timestamp NULL DEFAULT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cpf` varchar(14) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('consultor','operador','administrador') COLLATE utf8mb4_unicode_ci NOT NULL,
  `lembrete_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `criado_em` timestamp NULL DEFAULT NULL,
  `atualizado_em` timestamp NULL DEFAULT NULL,
  `foto_perfil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuario`
--

LOCK TABLES `usuario` WRITE;
/*!40000 ALTER TABLE `usuario` DISABLE KEYS */;
INSERT INTO `usuario` VALUES (11,'Julia','julsa.fsa@gmail.com',NULL,'$2y$12$rbALb3dB/4vQfuaWuC5pY.luJwDdbQ/x3NjVKCd9lD6GpVSGHKhW2','1111111111','1','administrador',NULL,'2026-03-11 13:41:15','2026-03-11 13:41:15',NULL),(12,'Miguel Vasconcelos','lmovasconcelos@gmail.com','2026-03-11 13:46:24','$2y$12$feWpdWXaMs87Ae0ZKI5mae0WxGz53bUqw5vwhEso2lfak1hAf8IGC','08810894510','2','administrador','tNFEjpk5gRlOiC7dPRkKyUQ3Eq2VPdLVgj8IpcjYJAJLAUYaZ08ETSPHlRFO','2026-03-11 13:42:19','2026-03-11 16:45:33','profile-photos/35tMxzsM9JkvMRWMPlwCEVM0bDRKumu7VJZug4ls.jpg'),(13,'teste1','alovasconcelos@gmail.com',NULL,'$2y$12$wH97kbyYZXvILVD3rXV2ke9iHYQ.iWHfWzDt0akO2iXxPpJ9pUdUa','22222222222','23','administrador',NULL,'2026-03-11 13:44:14','2026-03-11 13:44:14',NULL);
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

-- Dump completed on 2026-03-11 16:21:48
