-- MariaDB dump 10.19  Distrib 10.11.6-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: mysql    Database: feldspar
-- ------------------------------------------------------
-- Server version	8.0.40

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_profiles`
--

DROP TABLE IF EXISTS `account_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `account_profiles` (
  `account_id` int unsigned NOT NULL,
  `profile` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `account_profiles_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_profiles`
--

LOCK TABLES `account_profiles` WRITE;
/*!40000 ALTER TABLE `account_profiles` DISABLE KEYS */;
INSERT INTO `account_profiles` VALUES
(1,'Mi estas esperantisto.','2025-01-18 20:50:46');
/*!40000 ALTER TABLE `account_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accounts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `country` char(2) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
INSERT INTO `accounts` VALUES
(1,'Test','User','testuser','test@example.com','',1,'2025-01-18 20:50:46','2025-01-18 20:50:46');
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `confirm_account_tokens`
--

DROP TABLE IF EXISTS `confirm_account_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `confirm_account_tokens` (
  `account_id` int unsigned NOT NULL,
  `hash` char(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `account_id` (`account_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `confirm_account_tokens_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `confirm_account_tokens`
--

LOCK TABLES `confirm_account_tokens` WRITE;
/*!40000 ALTER TABLE `confirm_account_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `confirm_account_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `countries`
--

DROP TABLE IF EXISTS `countries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `countries` (
  `id` char(2) NOT NULL,
  `name` varchar(35) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `countries`
--

LOCK TABLES `countries` WRITE;
/*!40000 ALTER TABLE `countries` DISABLE KEYS */;
INSERT INTO `countries` VALUES
('AD','Andoro'),
('AE','Unuiĝintaj Arabaj Emirlandoj'),
('AF','Afganio'),
('AG','Antigvo kaj Barbudo'),
('AI','Angvilo'),
('AL','Albanio'),
('AM','Armenio'),
('AO','Angolo'),
('AR','Argentino'),
('AT','Aŭstrio'),
('AU','Aŭstralio'),
('AW','Arubo'),
('AZ','Azerbajĝano'),
('BA','Bosnio kaj Hercegovino'),
('BB','Barbado'),
('BD','Bangladeŝo'),
('BE','Belgio'),
('BF','Burkino'),
('BG','Bulgario'),
('BH','Barejno'),
('BI','Burundo'),
('BJ','Benino'),
('BN','Brunejo'),
('BO','Bolivio'),
('BR','Brazilo'),
('BS','Bahamoj'),
('BT','Butano'),
('BW','Bocvano'),
('BY','Belorusio'),
('BZ','Belizo'),
('CA','Kanado'),
('CD','Kongo DR'),
('CF','Centr-Afriko'),
('CG','Kongo PR'),
('CH','Svislando'),
('CI','Ebura Bordo'),
('CL','Ĉilio'),
('CM','Kameruno'),
('CN','Ĉinio'),
('CO','Kolombio'),
('CR','Kostariko'),
('CU','Kubo'),
('CV','Kaboverdo'),
('CY','Kipro'),
('CZ','Ĉeĥio'),
('DE','Germanio'),
('DJ','Ĝibutio'),
('DK','Danio'),
('DM','Dominiko'),
('DO','Dominika Respubliko'),
('DZ','Alĝerio'),
('EC','Ekvadoro'),
('EE','Estonio'),
('EG','Egiptio'),
('ER','Eritreo'),
('ES','Hispanio'),
('ET','Etiopio'),
('FI','Finnlando'),
('FJ','Fiĝio'),
('FM','Mikronezio'),
('FR','Francio'),
('GA','Gabono'),
('GB','Britio'),
('GD','Grenado'),
('GE','Kartvelio'),
('GH','Ganao'),
('GL','Gronlando'),
('GM','Gambio'),
('GN','Gvineo'),
('GQ','Ekvatora Gvineo'),
('GR','Grekio'),
('GT','Gvatemalo'),
('GW','Gvineo-Bisaŭo'),
('GY','Gujano'),
('HN','Honduro'),
('HR','Kroatio'),
('HT','Hatio'),
('HU','Hungario'),
('ID','Indonezio'),
('IE','Irlando'),
('IL','Israelo'),
('IN','Hinda Unio (Barato)'),
('IQ','Irako'),
('IR','Irano'),
('IS','Islando'),
('IT','Italio'),
('JM','Jamajko'),
('JO','Jordanio'),
('JP','Japanio'),
('KE','Kenjo'),
('KG','Kirgizio'),
('KH','Kamboĝo'),
('KI','Kiribato'),
('KM','Komoroj'),
('KN','Sankta-Kito kaj Neviso'),
('KP','Nord-Koreio'),
('KR','Sud-Koreio'),
('KW','Kuvajto'),
('KZ','Kazaĥio'),
('LA','Laoso'),
('LB','Libano'),
('LC','Sankta Lucio'),
('LI','Liĥtenŝtejno'),
('LK','Srilanko'),
('LR','Liberio'),
('LS','Lesoto'),
('LT','Litovio'),
('LU','Luksemburgo'),
('LV','Latvio'),
('LY','Libio'),
('MA','Maroko'),
('MC','Monako'),
('MD','Moldavio'),
('ME','Montenegro'),
('MG','Madagaskaro'),
('MH','Marŝaloj'),
('MK','Makedonio'),
('ML','Malio'),
('MM','Birmo'),
('MN','Mongolio'),
('MR','Maŭritanio'),
('MT','Malto'),
('MU','Maŭricio'),
('MV','Maldivoj'),
('MW','Malavio'),
('MX','Meksiko'),
('MY','Malajzio'),
('MZ','Mozambiko'),
('NA','Nambio'),
('NE','Niĝero'),
('NG','Nigerio'),
('NI','Nikaragvo'),
('NL','Nederlando'),
('NO','Norvegio'),
('NP','Nepalo'),
('NR','Nauro'),
('NZ','Nov-Zelando'),
('OM','Omano'),
('PA','Panamo'),
('PE','Peruo'),
('PG','Papuo-Nov-Gvineo'),
('PH','Filipinoj'),
('PK','Pakistano'),
('PL','Pollando'),
('PS','Palestino'),
('PT','Portugalio'),
('PW','Palaŭo'),
('PY','Paragvajo'),
('QA','Kataro'),
('RO','Rumanio'),
('RS','Serbio'),
('RU','Rusio'),
('RW','Ruando'),
('SA','Saŭda Arabio'),
('SB','Salomonoj'),
('SC','Sejŝeloj'),
('SD','Sudano'),
('SE','Svedio'),
('SG','Singapuro'),
('SI','Slovenio'),
('SK','Slovakio'),
('SL','Sieraleono'),
('SM','Sanmarino'),
('SN','Senegalo'),
('SO','Somalio'),
('SR','Surinamo'),
('SS','Sud-Sudano'),
('ST','Santomeo kaj Principeo'),
('SV','Salvadoro'),
('SY','Sirio'),
('SZ','Svazilando'),
('TD','Ĉado'),
('TG','Togolando'),
('TH','Tajlando'),
('TJ','Taĝikio'),
('TL','Orienta Timoro'),
('TM','Turkmeio'),
('TN','Tunizo'),
('TO','Tongo'),
('TR','Turkio'),
('TT','Trinidado kaj Tobago'),
('TV','Tuvalo'),
('TW','Tajvano'),
('TZ','Tanzanio'),
('UA','Ukranio'),
('UG','Ugando'),
('US','Usono'),
('UY','Urugvajo'),
('UZ','Uzbekio'),
('VA','Vatikano'),
('VC','Sankta Vincento kaj Grenadinoj'),
('VE','Venezuelo'),
('VN','Vjetnamio'),
('VU','Vanuatuo'),
('WS','Samoo'),
('YE','Jemeno'),
('ZA','Sud-Afriko'),
('ZM','Zambio'),
('ZW','Zimbabvo');
/*!40000 ALTER TABLE `countries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `oauth2_accounts`
--

DROP TABLE IF EXISTS `oauth2_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `oauth2_accounts` (
  `provider` enum('google','facebook') NOT NULL,
  `provider_id` varchar(100) NOT NULL,
  `account_id` int unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`provider`,`provider_id`),
  KEY `account_id` (`account_id`),
  CONSTRAINT `oauth2_accounts_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `oauth2_accounts`
--

LOCK TABLES `oauth2_accounts` WRITE;
/*!40000 ALTER TABLE `oauth2_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `oauth2_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_tokens`
--

DROP TABLE IF EXISTS `otp_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otp_tokens` (
  `account_id` int unsigned NOT NULL,
  `hash` char(60) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `account_id` (`account_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `otp_tokens_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `otp_tokens`
--

LOCK TABLES `otp_tokens` WRITE;
/*!40000 ALTER TABLE `otp_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `passwords`
--

DROP TABLE IF EXISTS `passwords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `passwords` (
  `account_id` int unsigned NOT NULL,
  `hash` char(60) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `passwords_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `passwords`
--

LOCK TABLES `passwords` WRITE;
/*!40000 ALTER TABLE `passwords` DISABLE KEYS */;
INSERT INTO `passwords` VALUES
(1,'$2y$10$ZhzEkUVSW0K00xf1rmZ9xOvKDQzIrx15/td.LjEmvgnVWwsQS9fha','2025-01-18 20:50:46');
/*!40000 ALTER TABLE `passwords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `profiles`
--

DROP TABLE IF EXISTS `profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `profiles` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `website` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `account_id` (`account_id`),
  CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `profiles`
--

LOCK TABLES `profiles` WRITE;
/*!40000 ALTER TABLE `profiles` DISABLE KEYS */;
INSERT INTO `profiles` VALUES
(1,1,'','','','2025-01-18 20:50:46','2025-02-24 05:44:15');
/*!40000 ALTER TABLE `profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `account_id` int unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `account_id` (`account_id`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
INSERT INTO `tasks` VALUES
(1,1,'test 1','abc','2025-02-24 05:48:15','2025-02-24 05:48:15'),
(2,1,'test 2','xyz','2025-02-24 05:48:25','2025-02-24 05:48:25');
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'feldspar'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-03-13 22:41:30
