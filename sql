-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 09 mai 2025 à 17:41
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `jgr`
--

DELIMITER $$
--
-- Procédures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_all_stage_statistics` ()   BEGIN
    -- Define the stages
    SET @stages = 'Coupe,V1,V2,V3,Pantalon,Repassage,P_fini,Exported';
    
    -- Create a temporary table to hold results
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_stage_stats (
        stage VARCHAR(100),
        in_count INT,
        out_count INT,
        current_count INT,
        completion_percentage DECIMAL(5,2)
    );
    
    -- Clear the temporary table
    DELETE FROM temp_stage_stats;
    
    -- Process each stage
    WHILE LENGTH(@stages) > 0 DO
        SET @stage_name = SUBSTRING_INDEX(@stages, ',', 1);
        SET @stages = IF(LOCATE(',', @stages) > 0, 
                        SUBSTRING(@stages FROM LOCATE(',', @stages) + 1), 
                        '');
        
        -- Current count in this stage
        SELECT COUNT(*) INTO @current 
        FROM barcodes 
        WHERE stage = @stage_name;
        
        -- Total items that came into this stage
        SELECT COUNT(*) INTO @total_in 
        FROM barcode_movements 
        WHERE to_stage = @stage_name;
        
        -- Total items that left this stage
        SELECT COUNT(*) INTO @total_out 
        FROM barcode_movements 
        WHERE from_stage = @stage_name;
        
        -- Calculate completion percentage
        SET @completion_pct = IF(@total_in > 0, ROUND((@total_out / @total_in) * 100, 2), 0);
        
        -- Insert into temporary table
        INSERT INTO temp_stage_stats 
        VALUES (@stage_name, @total_in, @total_out, @current, @completion_pct);
    END WHILE;
    
    -- Return the results
    SELECT * FROM temp_stage_stats;
    
    -- Drop the temporary table
    DROP TEMPORARY TABLE temp_stage_stats;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_stage_statistics` (IN `stage_name` VARCHAR(100))   BEGIN
    -- Current count in this stage
    SELECT COUNT(*) INTO @current 
    FROM barcodes 
    WHERE stage = stage_name;
    
    -- Total items that came into this stage
    SELECT COUNT(*) INTO @total_in 
    FROM barcode_movements 
    WHERE to_stage = stage_name;
    
    -- Total items that left this stage
    SELECT COUNT(*) INTO @total_out 
    FROM barcode_movements 
    WHERE from_stage = stage_name;
    
    -- Return the results
    SELECT 
        stage_name AS stage,
        @total_in AS in_count,
        @total_out AS out_count,
        @current AS current_count,
        CASE 
            WHEN @total_in > 0 THEN ROUND((@total_out / @total_in) * 100, 2)
            ELSE 0
        END AS completion_percentage;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_cleanup_history_tables` ()   BEGIN
    -- Delete records older than 3 months from both history tables
    DELETE FROM jgr_quantity_coupe_history
    WHERE action_time < DATE_SUB(NOW(), INTERVAL 3 MONTH);
   
    DELETE FROM jgr_barcodes_history
    WHERE action_time < DATE_SUB(NOW(), INTERVAL 3 MONTH);
   
    -- Optional: Log the cleanup operation
    SELECT CONCAT('History tables cleaned up at ', NOW()) AS cleanup_log;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `barcodes`
--

CREATE TABLE `barcodes` (
  `id` int(11) NOT NULL,
  `of_number` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `piece_name` varchar(100) DEFAULT NULL,
  `order_str` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `stage` varchar(100) DEFAULT NULL,
  `chef` varchar(100) DEFAULT NULL,
  `full_barcode_name` varchar(255) DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `barcodes`
--
DELIMITER $$
CREATE TRIGGER `tr_barcode_stage_change` AFTER UPDATE ON `barcodes` FOR EACH ROW BEGIN
    -- Only record if the stage has changed
    IF OLD.stage != NEW.stage THEN
        INSERT INTO barcode_movements (barcode_id, from_stage, to_stage, user_id)
        VALUES (NEW.id, OLD.stage, NEW.stage, NULL); -- User ID would be set in your application
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_barcodes_delete` AFTER DELETE ON `barcodes` FOR EACH ROW BEGIN
    INSERT INTO jgr_barcodes_history (
        action_type, id, of_number, size, category, piece_name,
        order_str, status, stage, chef, full_barcode_name, last_update
    )
    VALUES (
        'DELETE', OLD.id, OLD.of_number, OLD.size, OLD.category, OLD.piece_name,
        OLD.order_str, OLD.status, OLD.stage, OLD.chef, OLD.full_barcode_name, OLD.last_update
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_barcodes_insert` AFTER INSERT ON `barcodes` FOR EACH ROW BEGIN
    INSERT INTO jgr_barcodes_history (
        action_type, id, of_number, size, category, piece_name,
        order_str, status, stage, chef, full_barcode_name, last_update
    )
    VALUES (
        'INSERT', NEW.id, NEW.of_number, NEW.size, NEW.category, NEW.piece_name,
        NEW.order_str, NEW.status, NEW.stage, NEW.chef, NEW.full_barcode_name, NEW.last_update
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_barcodes_update` AFTER UPDATE ON `barcodes` FOR EACH ROW BEGIN
    INSERT INTO jgr_barcodes_history (
        action_type, id, of_number, size, category, piece_name,
        order_str, status, stage, chef, full_barcode_name, last_update
    )
    VALUES (
        'UPDATE', NEW.id, NEW.of_number, NEW.size, NEW.category, NEW.piece_name,
        NEW.order_str, NEW.status, NEW.stage, NEW.chef, NEW.full_barcode_name, NEW.last_update
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `barcode_movements`
--

CREATE TABLE `barcode_movements` (
  `id` int(11) NOT NULL,
  `barcode_id` int(11) NOT NULL,
  `from_stage` varchar(100) DEFAULT NULL,
  `to_stage` varchar(100) NOT NULL,
  `movement_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jgr_barcodes_history`
--

CREATE TABLE `jgr_barcodes_history` (
  `history_id` int(11) NOT NULL,
  `action_type` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `id` int(11) DEFAULT NULL,
  `of_number` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `piece_name` varchar(100) DEFAULT NULL,
  `order_str` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `stage` varchar(100) DEFAULT NULL,
  `chef` varchar(100) DEFAULT NULL,
  `full_barcode_name` varchar(255) DEFAULT NULL,
  `last_update` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `jgr_quantity_coupe_history`
--

CREATE TABLE `jgr_quantity_coupe_history` (
  `history_id` int(11) NOT NULL,
  `action_type` enum('INSERT','UPDATE','DELETE') NOT NULL,
  `action_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `id` int(11) DEFAULT NULL,
  `of_number` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `piece_name` varchar(100) DEFAULT NULL,
  `solped_client` varchar(100) DEFAULT NULL,
  `pedido_client` varchar(100) DEFAULT NULL,
  `color_tissus` varchar(100) DEFAULT NULL,
  `principale_quantity` int(11) DEFAULT NULL,
  `quantity_coupe` int(11) DEFAULT NULL,
  `manque` int(11) DEFAULT NULL,
  `suv_plus` int(11) DEFAULT NULL,
  `lastupdate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `quantity_coupe`
--

CREATE TABLE `quantity_coupe` (
  `id` int(11) NOT NULL,
  `of_number` varchar(50) DEFAULT NULL,
  `size` varchar(50) DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `piece_name` varchar(100) DEFAULT NULL,
  `solped_client` varchar(100) DEFAULT NULL,
  `pedido_client` varchar(100) DEFAULT NULL,
  `color_tissus` varchar(100) DEFAULT NULL,
  `principale_quantity` int(11) DEFAULT NULL,
  `quantity_coupe` int(11) DEFAULT NULL,
  `manque` int(11) DEFAULT NULL,
  `suv_plus` int(11) DEFAULT NULL,
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déclencheurs `quantity_coupe`
--
DELIMITER $$
CREATE TRIGGER `tr_quantity_coupe_delete` AFTER DELETE ON `quantity_coupe` FOR EACH ROW BEGIN
    INSERT INTO jgr_quantity_coupe_history (
        action_type, id, of_number, size, category, piece_name,
        solped_client, pedido_client, color_tissus, principale_quantity,
        quantity_coupe, manque, suv_plus, lastupdate
    )
    VALUES (
        'DELETE', OLD.id, OLD.of_number, OLD.size, OLD.category, OLD.piece_name,
        OLD.solped_client, OLD.pedido_client, OLD.color_tissus, OLD.principale_quantity,
        OLD.quantity_coupe, OLD.manque, OLD.suv_plus, OLD.lastupdate
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_quantity_coupe_insert` AFTER INSERT ON `quantity_coupe` FOR EACH ROW BEGIN
    INSERT INTO jgr_quantity_coupe_history (
        action_type, id, of_number, size, category, piece_name,
        solped_client, pedido_client, color_tissus, principale_quantity,
        quantity_coupe, manque, suv_plus, lastupdate
    )
    VALUES (
        'INSERT', NEW.id, NEW.of_number, NEW.size, NEW.category, NEW.piece_name,
        NEW.solped_client, NEW.pedido_client, NEW.color_tissus, NEW.principale_quantity,
        NEW.quantity_coupe, NEW.manque, NEW.suv_plus, NEW.lastupdate
    );
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_quantity_coupe_update` AFTER UPDATE ON `quantity_coupe` FOR EACH ROW BEGIN
    INSERT INTO jgr_quantity_coupe_history (
        action_type, id, of_number, size, category, piece_name,
        solped_client, pedido_client, color_tissus, principale_quantity,
        quantity_coupe, manque, suv_plus, lastupdate
    )
    VALUES (
        'UPDATE', NEW.id, NEW.of_number, NEW.size, NEW.category, NEW.piece_name,
        NEW.solped_client, NEW.pedido_client, NEW.color_tissus, NEW.principale_quantity,
        NEW.quantity_coupe, NEW.manque, NEW.suv_plus, NEW.lastupdate
    );
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `is_active`, `last_login`, `created_at`) VALUES
(1, 'BL4Z3', '$2y$10$n1wVx/ulr3aJo0b57oMpduxYEM8Z2C2dM6Fv8V/TMH1RQEZvAE4Gi', 1, '2025-05-09 12:34:21', '2025-05-06 14:52:33');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `barcodes`
--
ALTER TABLE `barcodes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `full_barcode_name` (`full_barcode_name`),
  ADD KEY `idx_barcode_fields` (`of_number`,`size`,`category`,`piece_name`);

--
-- Index pour la table `barcode_movements`
--
ALTER TABLE `barcode_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `barcode_id` (`barcode_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_movements_stages` (`from_stage`,`to_stage`);

--
-- Index pour la table `jgr_barcodes_history`
--
ALTER TABLE `jgr_barcodes_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Index pour la table `jgr_quantity_coupe_history`
--
ALTER TABLE `jgr_quantity_coupe_history`
  ADD PRIMARY KEY (`history_id`);

--
-- Index pour la table `quantity_coupe`
--
ALTER TABLE `quantity_coupe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_quantity_coupe_barcode` (`of_number`,`size`,`category`,`piece_name`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `barcodes`
--
ALTER TABLE `barcodes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1204;

--
-- AUTO_INCREMENT pour la table `barcode_movements`
--
ALTER TABLE `barcode_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `jgr_barcodes_history`
--
ALTER TABLE `jgr_barcodes_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `jgr_quantity_coupe_history`
--
ALTER TABLE `jgr_quantity_coupe_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `quantity_coupe`
--
ALTER TABLE `quantity_coupe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `barcode_movements`
--
ALTER TABLE `barcode_movements`
  ADD CONSTRAINT `barcode_movements_ibfk_1` FOREIGN KEY (`barcode_id`) REFERENCES `barcodes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `barcode_movements_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `quantity_coupe`
--
ALTER TABLE `quantity_coupe`
  ADD CONSTRAINT `fk_quantity_coupe_barcode` FOREIGN KEY (`of_number`,`size`,`category`,`piece_name`) REFERENCES `barcodes` (`of_number`, `size`, `category`, `piece_name`) ON UPDATE CASCADE;

DELIMITER $$
--
-- Évènements
--
CREATE DEFINER=`root`@`localhost` EVENT `evt_cleanup_quantity_coupe_history` ON SCHEDULE EVERY 1 MONTH STARTS '2025-05-07 15:49:33' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DELETE FROM jgr_quantity_coupe_history
    WHERE action_time < DATE_SUB(NOW(), INTERVAL 3 MONTH);
END$$

CREATE DEFINER=`root`@`localhost` EVENT `evt_cleanup_barcodes_history` ON SCHEDULE EVERY 1 MONTH STARTS '2025-05-07 15:49:33' ON COMPLETION NOT PRESERVE ENABLE DO BEGIN
    DELETE FROM jgr_barcodes_history
    WHERE action_time < DATE_SUB(NOW(), INTERVAL 3 MONTH);
END$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
