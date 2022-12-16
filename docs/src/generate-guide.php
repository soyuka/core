<?php 

declare(strict_types=1);

$sections = [];
$linesOfCode = $linesOfText = $currentSection = 0;
$sections[$currentSection] = ['text' => [], 'code' => []];
$regex = '/^\s*\/\//';

$handle = fopen($argv[1] ?? null, 'r');
if (!$handle) {
    fwrite(STDERR, sprintf('Error opening %s. %s', $argv[1], \PHP_EOL));
    exit(1);
}

fwrite(STDERR, sprintf('Creating guide %s.%s', $argv[1], \PHP_EOL));
while (($line = fgets($handle)) !== false) {
    if (!isset($sections[$currentSection]['text'])) {
        $sections[$currentSection] = ['text' => [], 'code' => []];
    }

    if (!trim($line)) {
        $sections[$currentSection]['text'][] = $line;
        $sections[$currentSection]['code'][] = $line;
        continue;
    }

    if (preg_match($regex, $line)) {
        $sections[$currentSection]['text'][] = preg_replace($regex, '', $line);
        ++$linesOfText;

        if ($linesOfCode) {
            ++$currentSection;
            $linesOfCode = $linesOfText = 0;
        }
        continue;
    }

    $sections[$currentSection]['code'][] = $line;
    ++$linesOfCode;

    if ($linesOfText && $linesOfCode >= $linesOfText) {
        ++$currentSection;
        $linesOfCode = $linesOfText = 0;
    }
}

fclose($handle);

?>

<div className="sections">

<?php
foreach ($sections as $i => $section) {
    ?>
  <div className="section" id="section-<?php echo $i; ?>">
    <div className="annotation">
    <a className="anchor" href="#section-<?php echo $i; ?>">&#x00a7;</a>
<?php
    echo implode(\PHP_EOL, $section['text'] ?? [\PHP_EOL]);
    ?>
    </div>
    <div className="content">

```php
<?php
    echo implode(\PHP_EOL, $section['code'] ?? [\PHP_EOL]);
    ?>
```
    </div>
  </div>
<?php
}
?>
</div>
