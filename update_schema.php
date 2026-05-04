<?php
require 'vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$connectionParams = [
    'url' => 'mysql://root:@127.0.0.1:3306/uniearn_db?serverVersion=10.4.32-MariaDB&charset=utf8mb4',
];
$conn = DriverManager::getConnection($connectionParams);

$sql = "
ALTER TABLE portfolio CHANGE created_At created_At DATETIME NOT NULL;
ALTER TABLE portfolio ADD CONSTRAINT FK_A9ED10624DCE947A FOREIGN KEY (freelancerId) REFERENCES freelancer (idFreelancer) ON DELETE CASCADE;
DROP INDEX freelancer ON portfolio;
CREATE INDEX IDX_A9ED10624DCE947A ON portfolio (freelancerId);
ALTER TABLE portfolioitem DROP FOREIGN KEY fk_portfolioitem_portfolio;
ALTER TABLE portfolioitem CHANGE title title VARCHAR(255) DEFAULT NULL, CHANGE description description VARCHAR(500) DEFAULT NULL, CHANGE technologies technologies VARCHAR(500) DEFAULT NULL, CHANGE imageUrl imageUrl VARCHAR(500) DEFAULT NULL, CHANGE projectUrl projectUrl VARCHAR(255) DEFAULT NULL, CHANGE githubUrl githubUrl VARCHAR(255) DEFAULT NULL, CHANGE created_At created_At DATETIME NOT NULL;
ALTER TABLE portfolioitem ADD CONSTRAINT FK_D6C82F0B55073B34 FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON DELETE CASCADE;
DROP INDEX portfolio ON portfolioitem;
CREATE INDEX IDX_D6C82F0B55073B34 ON portfolioitem (idPortfolio);
ALTER TABLE portfolioitem ADD CONSTRAINT fk_portfolioitem_portfolio FOREIGN KEY (idPortfolio) REFERENCES portfolio (idPortfolio) ON UPDATE CASCADE ON DELETE CASCADE;
CREATE UNIQUE INDEX UNIQ_8D93D649E7927C74 ON user (email);
";

$conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');

$queries = array_filter(array_map('trim', explode(';', $sql)));

foreach ($queries as $query) {
    if (empty($query)) continue;
    try {
        $conn->executeStatement($query);
        echo "OK: $query\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

$conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');

echo "Done.\n";
