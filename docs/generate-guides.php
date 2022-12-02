<?php

$sections = [];
$linesOfCode = $linesOfText = $currentSection = 0;
$sections[$currentSection] = ['text' => [], 'code' => []];

$regex = '/^\s*\/\//';
$handle = fopen('./guides/0-ApiResource.php', 'r');
if ($handle) {

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
        $linesOfText++;

        if ($linesOfCode) {
          $currentSection++;
          $linesOfCode = $linesOfText = 0;
        }
        continue;
      }

      $sections[$currentSection]['code'][] = $line;
      $linesOfCode++;

      if ($linesOfText && $linesOfCode >= $linesOfText) {
        $currentSection++;
        $linesOfCode = $linesOfText = 0;
      }
    }

    fclose($handle);
}

?>

<ul className="sections">

<?php
foreach ($sections as $i => $section) {
?>
  <li id="section-<?php echo $i; ?>">
    <div className="annotation">
      <div className="sswrap ">
      <a className="ss" href="#section-<?php echo $i ?>">&#x00a7;</a>
      </div>
<?php
echo implode(PHP_EOL, $section['text'] ?? [PHP_EOL]);
?>
    </div>
    <div className="content">

```php
<?php
echo implode(PHP_EOL, $section['code'] ?? [PHP_EOL]);
?>
```
    </div>
  </li>
<?php
}
?>
</ul>
