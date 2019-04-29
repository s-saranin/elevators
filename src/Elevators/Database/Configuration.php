<?php


namespace Elevators\Database;


class Configuration extends AbstractConnection
{
    /**
     * Получение конфигурации приложения
     *
     * @return array
     */
    public function getConfiguration()
    {
        $configuration = [];

        $query = pg_query($this->connection, 'SELECT option, value FROM configuration');
        while ($setting = pg_fetch_assoc($query)) {
            $configuration[$setting['option']] = $setting['value'];
        }

        return $configuration;
    }

    /**
     * Установка конфигурации приложения
     *
     * @param array $configuration
     * @return bool
     */
    public function setupConfiguration(array $configuration)
    {
        $result = false;

        pg_query($this->connection,"BEGIN");

        $truncateResult = pg_query($this->connection,"TRUNCATE TABLE configuration");

        $values = [];
        foreach ($configuration as $option => $value) {
            $option = pg_escape_string($option);
            $value = pg_escape_string($value);
            $values[] = sprintf("('{$option}', '{$value}')");
        }
        $values = implode(', ', $values);
        $query = "INSERT INTO configuration(option, value) VALUES {$values}";

        $setupResult = pg_query($this->connection, $query);

        if ($truncateResult && $setupResult) {
            pg_query($this->connection,"COMMIT");
            $result = true;
        } else {
            pg_query($this->connection,"ROLLBACK");
        }

        return $result;
    }
}