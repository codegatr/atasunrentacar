<?php
/**
 * Veritabani Baglantisi - PDO/MySQL
 */
defined('ATASU') or exit('403');

class DB
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $opt = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci, time_zone = '+03:00'",
            ];
            try {
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, $opt);
            } catch (PDOException $e) {
                if (DEBUG_MODE) {
                    die('DB Hatasi: ' . htmlspecialchars($e->getMessage()));
                }
                http_response_code(500);
                die('Veritabanina baglanilamadi.');
            }
        }
        return self::$pdo;
    }

    public static function tablo(string $isim): string
    {
        return DB_PREFIX . $isim;
    }

    public static function sorgu(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public static function tek(string $sql, array $params = []): ?array
    {
        $row = self::sorgu($sql, $params)->fetch();
        return $row ?: null;
    }

    public static function liste(string $sql, array $params = []): array
    {
        return self::sorgu($sql, $params)->fetchAll();
    }

    public static function ekle(string $tablo, array $veri): int
    {
        $kolonlar = array_keys($veri);
        $placeholders = array_map(fn($k) => ':' . $k, $kolonlar);
        $sql = 'INSERT INTO ' . self::tablo($tablo) . ' (' . implode(',', $kolonlar) . ') VALUES (' . implode(',', $placeholders) . ')';
        self::sorgu($sql, $veri);
        return (int)self::pdo()->lastInsertId();
    }

    public static function guncelle(string $tablo, array $veri, string $where, array $whereParams = []): int
    {
        $set = [];
        foreach (array_keys($veri) as $k) {
            $set[] = $k . ' = :set_' . $k;
        }
        $params = [];
        foreach ($veri as $k => $v) {
            $params['set_' . $k] = $v;
        }
        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }
        $sql = 'UPDATE ' . self::tablo($tablo) . ' SET ' . implode(', ', $set) . ' WHERE ' . $where;
        return self::sorgu($sql, $params)->rowCount();
    }

    public static function sil(string $tablo, string $where, array $params = []): int
    {
        $sql = 'DELETE FROM ' . self::tablo($tablo) . ' WHERE ' . $where;
        return self::sorgu($sql, $params)->rowCount();
    }

    public static function tabloVarMi(string $tablo): bool
    {
        $stmt = self::pdo()->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1");
        $stmt->execute([self::tablo($tablo)]);
        return (bool)$stmt->fetch();
    }
}
