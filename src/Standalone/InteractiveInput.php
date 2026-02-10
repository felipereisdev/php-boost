<?php

namespace FelipeReisDev\PhpBoost\Standalone;

class InteractiveInput
{
    public static function ask($question, $default = null)
    {
        $defaultText = $default ? " [{$default}]" : '';
        echo "{$question}{$defaultText}: ";
        
        $handle = fopen('php://stdin', 'r');
        $line = fgets($handle);
        fclose($handle);
        
        $answer = trim($line);
        
        return $answer !== '' ? $answer : $default;
    }

    public static function confirm($question, $default = true)
    {
        $defaultText = $default ? 'Y/n' : 'y/N';
        $answer = self::ask("{$question} ({$defaultText})");
        
        if ($answer === '') {
            return $default;
        }
        
        $answer = strtolower($answer);
        return in_array($answer, ['y', 'yes', 'sim', 's']);
    }

    public static function multiselect($question, $options, $defaults = [])
    {
        echo "\n{$question}\n";
        echo "(Use espaço para marcar/desmarcar, Enter para confirmar)\n\n";
        
        $selected = $defaults;
        $currentIndex = 0;
        
        while (true) {
            self::clearScreen();
            echo "\n{$question}\n";
            echo "(Use ↑↓ para navegar, espaço para marcar, Enter para confirmar)\n\n";
            
            foreach ($options as $index => $option) {
                $marker = in_array($option['value'], $selected) ? '[x]' : '[ ]';
                $cursor = $index === $currentIndex ? '>' : ' ';
                $label = $option['label'];
                
                echo "{$cursor} {$marker} {$label}\n";
            }
            
            $key = self::readKey();
            
            if ($key === "\n") {
                break;
            } elseif ($key === ' ') {
                $value = $options[$currentIndex]['value'];
                if (in_array($value, $selected)) {
                    $selected = array_values(array_diff($selected, [$value]));
                } else {
                    $selected[] = $value;
                }
            } elseif ($key === "\033[A") {
                $currentIndex = max(0, $currentIndex - 1);
            } elseif ($key === "\033[B") {
                $currentIndex = min(count($options) - 1, $currentIndex + 1);
            }
        }
        
        return $selected;
    }

    public static function select($question, $options, $default = 0)
    {
        echo "\n{$question}\n\n";
        
        foreach ($options as $index => $option) {
            echo "  " . ($index + 1) . ". {$option['label']}\n";
        }
        
        $answer = self::ask("\nEscolha uma opção", $default + 1);
        $index = intval($answer) - 1;
        
        if ($index >= 0 && $index < count($options)) {
            return $options[$index]['value'];
        }
        
        return $options[$default]['value'];
    }

    private static function readKey()
    {
        system('stty cbreak -echo');
        $key = fgetc(STDIN);
        
        if (ord($key) === 27) {
            $key .= fgetc(STDIN);
            $key .= fgetc(STDIN);
        }
        
        system('stty -cbreak echo');
        
        return $key;
    }

    private static function clearScreen()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            system('cls');
        } else {
            system('clear');
        }
    }
}
