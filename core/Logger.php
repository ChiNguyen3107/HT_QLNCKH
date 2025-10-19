<?php

/**
 * Logger Class
 * Simple logging utility for the application
 */
class Logger
{
    private static $logDir = 'logs';
    private static $logFile = 'application.log';
    
    public static function init($logDir = 'logs')
    {
        self::$logDir = $logDir;
        
        // Create log directory if it doesn't exist
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
    }
    
    public static function log($level, $message, $context = [])
    {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        $logFile = self::$logDir . '/' . self::$logFile;
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public static function error($message, $context = [])
    {
        self::log('ERROR', $message, $context);
    }
    
    public static function warning($message, $context = [])
    {
        self::log('WARNING', $message, $context);
    }
    
    public static function info($message, $context = [])
    {
        self::log('INFO', $message, $context);
    }
    
    public static function debug($message, $context = [])
    {
        self::log('DEBUG', $message, $context);
    }
    
    public static function security($message, $context = [])
    {
        self::log('SECURITY', $message, $context);
    }
}
