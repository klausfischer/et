
<?php 
	require("Enhancer.php");

	$eh = new Enhancer();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>ET</title>
	<link rel="stylesheet" href="css/uikit.almost-flat.min.css" />
	<link rel="stylesheet" href="css/style.css" />
    <script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="js/uikit.min.js"></script>
    <script src="js/main.js"></script>

	<link rel="icon" type="image/png" href="favicon-32x32.png?v=2" sizes="32x32" />
	<link rel="icon" type="image/png" href="favicon-16x16.png" sizes="16x16" />
</head>
<body>
	<header class="uk-container uk-container-center">
		<h1 class="uk-heading-large uk-margin-large-top">ET - Content enhancer</h1>
		<p class="uk-text-large uk-margin-large-bottom">Paste your raw text in the field below and check your desired options to save some time on the internet!</p>
	</header>

	<main class="uk-container uk-container-center">
		
		<section class="uk-grid section section--input">
			
			<form id="form-i" method="post" action="/" class="uk-form uk-width-1-1 uk-form-stacked">
				<div class="uk-form-row">
					<textarea id="form-i-input" cols="30" rows="5" name="form-i-input" placeholder="Paste your text here" class="uk-width-1-1 uk-width-medium-1-2"><?php if ($_POST['form-i-input']) { echo $_POST['form-i-input']; }?></textarea>
				</div>
				<div class="uk-form-row">
					<input type="checkbox" id="form-i-images-locations" name="form-i-images-locations" <?php if (isset($_POST['form-i-images-locations'])) { echo "checked";} ?> >
					<label for="form-i-images-locations">Images for locations</label>
					<input type="checkbox" id="form-i-images-people" name="form-i-images-people" <?php if (isset($_POST['form-i-images-people'])) { echo "checked";} ?> >
					<label for="form-i-images-people">Images for people</label>	
				</div>
				<div class="uk-form-row">
					<button id="button--submit" class="uk-button uk-button-primary uk-button-large" type="submit" disabled><i class="uk-icon uk-icon-flask"></i>Enhance</button>	
				</div>
				
			</form>
		</section>
		
		<?php if ($_POST['form-i-input']) { 
				$eh->analyzeText($_POST['form-i-input']);
			?>
		
		<section class="uk-grid section section--output uk-margin-large-bottom">
			<h2 class="uk-width-1-1 uk-margin-bottom">Result</h2>
			<div class="uk-width-1-1">
				
					<?php echo $eh->getEnhancedText(); ?>
				
				
			</div>
		</section>
		<section class="section section--meta uk-block uk-block-muted uk-margin-bottom-large">
			<div class="uk-container">
				<h2 class="uk-margin-bottom">Meta Data</h2>
				<div class="uk-grid">
				<?php $meta = $eh->getMeta(); 
				foreach ($meta as $type => $values) {
					echo "<div class='uk-width-1-4'>";
					echo "<h3>" . $type . "</h3>";
					echo "<ul class='uk-list uk-list-line'>";
					echo "<li>" . implode ("</li><li>", $values) . "</li>";
					echo "</ul>";
					echo "</div>";
				}


				?>
				</div>
			</div>
		</section>
		<?php }	?> 
	</main>

</body>
</html>