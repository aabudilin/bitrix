<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$this->setFrameMode(true);
?>

<?if(count($arResult['ITEMS']) > 0):?>
	<div class="fotogallery">
		<?foreach($arResult['ITEMS'] as $arItem):?>
		<div class="item">
			<a href="<?=$arItem['FULL_IMG']?>" data-fancybox="gallery">
				<img src="<?=$arItem['PREV_IMG']?>" />
			</a>
		</div>
		<?endforeach?>
	</div>
<?endif?>