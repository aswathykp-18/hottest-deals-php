/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.14-MariaDB, for debian-linux-gnu (aarch64)
--
-- Host: localhost    Database: hrms
-- ------------------------------------------------------
-- Server version	10.11.14-MariaDB-0+deb12u2

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
-- Table structure for table `hottest_deals`
--

DROP TABLE IF EXISTS `hottest_deals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hottest_deals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `agent_name` varchar(100) NOT NULL,
  `area` varchar(150) NOT NULL,
  `project_name` varchar(150) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `property_type` varchar(50) DEFAULT NULL,
  `op_text` varchar(30) DEFAULT NULL,
  `sp_text` varchar(30) DEFAULT NULL,
  `op_amount` decimal(15,2) DEFAULT NULL,
  `sp_amount` decimal(15,2) DEFAULT NULL,
  `payout` varchar(255) DEFAULT NULL,
  `status_text` varchar(100) DEFAULT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hottest_deals`
--

LOCK TABLES `hottest_deals` WRITE;
/*!40000 ALTER TABLE `hottest_deals` DISABLE KEYS */;
INSERT INTO `hottest_deals` VALUES
(2,'Mahitab Ahmed','Town Square','Kaya','3 Bed','Apartment','1.66M','1.9M',1660000.00,1900000.00,'30% Paid / Transfer at 70%','Aug-26',2,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(3,'Salah','Emaar Beachfront','Bayview By Address','3 Bed','Apartment','9.8M','8.5M',9800000.00,8500000.00,'60% Paid','2028',3,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(4,'Ashwin','Emaar South','Greenway 2','3 Bed','Townhouse','2.85M','2.65M',2850000.00,2650000.00,'60% Paid','2027',4,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(5,'Venera','Dubai Hills','Palace Residences','1 Bed','Apartment','1.76M','1.6M',1760000.00,1600000.00,'60% Paid','2027',5,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(7,'Ashwin','Emaar South','Greenway 2','4 Bed','Townhouse','3.23M','3.1M',3230000.00,3100000.00,'60% Paid','',7,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(8,'Joelle','Damac Islands','Maldives','4 Bed','Townhouse','2.58M','2.55M',2580000.00,2500000.00,'32% Paid / Transfer at 40%','',8,1,'2026-03-26 13:00:33','2026-04-02 09:48:08'),
(9,'Ameera','Dubai Hills','Parkside Views','3 Bed','Apartment','3.42M','3.45M',3420000.00,3450000.00,'90% Paid','HO July 2026',9,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(10,'Yasmine','Emaar South','Fairway Villas 1','3 Bed','SA Villa','3.85M','4.4M',3850000.00,4400000.00,'Fully Paid','Ready',10,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(11,'Mridul','NAS Gardens P3','NAS Gardens P3','4 Bed','Villa','14.6M','15.1M',14600000.00,15100000.00,'50% Paid','Sep-26',11,1,'2026-03-26 13:00:33','2026-04-02 09:46:08'),
(12,'Mridul','NAS Gardens P3','NAS Gardens P3','4 Bed','Villa','12.1M','12.5M',12100000.00,12500000.00,'50% Paid','Sep-26',12,1,'2026-03-26 13:00:33','2026-04-02 09:46:18'),
(13,'Hassan','Mina Rashid','Marina Views','1 Bed','Apartment','1.76M','1.65M',1760000.00,1650000.00,'40% Paid','2028',13,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(14,'Sarah','The Valley','Alana','5 Bed','SD Villa','5.7M','5.4M',5700000.00,5400000.00,'80% Paid','Sep-26',14,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(15,'Nurali','Damac Hills 2','Violet 1','4 Bed','Townhouse','1.89M','1.8M',1890000.00,1800000.00,'50% Paid','Jun-26',15,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(16,'Nurali','Dubai Hills','Parkwood','2 Bed','Apartment','2.8M','2.5M',2800000.00,2500000.00,'30% Paid','2026 July',16,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(17,'Ravina','Marytime City','Orise','1 Bed','Apartment','2.11M','2.11M',2110000.00,2110000.00,'30% Paid','2028 Sep',17,1,'2026-03-26 13:00:33','2026-03-26 13:00:33'),
(18,'Sawaiz','Dubai Hills','Club Drive','2 bedroom','Apartment','2.28M','2M',2280000.00,200000.00,'80% paid','Q4 2026',0,1,'2026-04-06 13:48:35','2026-04-06 13:48:35'),
(19,'Sawaiz',' Dubai Hills\r\n\r\n\r\n\r\n\r\n\r\n','Golf Grand','1 bedroom','Apartment','1.43M','1.53M',1430000.00,1530000.00,'Fully Paid','Ready',0,1,'2026-04-06 13:57:15','2026-04-06 13:57:15');
/*!40000 ALTER TABLE `hottest_deals` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-04-17 11:48:23
