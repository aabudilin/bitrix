<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
define ('API_KEY','THIS_API_KEY');
?>

<style>
	.full-map {
		width:100%;
		height:600px;
	}
</style>

<div id="fullmap" class="full-map"></div>

<script src="https://api-maps.yandex.ru/2.1/?lang=ru-RU&apikey=<?=API_KEY?>" type="text/javascript"></script>
<script>

let data = {
	"type": "FeatureCollection",
	"features": [
<?foreach($arResult["ITEMS"] as $key => $arItem):?>
	<?
		$contentBaloon = '';
		if ($arItem['PROPERTIES']['ADDR']['VALUE']) {
			$contentBaloon .= '<p>'.$arItem['PROPERTIES']['ADDR']['VALUE'].'</p>';
		}
		if ($arItem['PROPERTIES']['PHONE']['VALUE']) {
			$contentBaloon .= '<p>'.$arItem['PROPERTIES']['PHONE']['VALUE'].'</p>';
		}
		if ($arItem['PROPERTIES']['URL']['VALUE']) {
			$contentBaloon .= "<p><a href='http://".$arItem['PROPERTIES']['URL']['VALUE']."' target='_blanck'>".$arItem['PROPERTIES']['URL']['VALUE']."</a></p>";
		}
	?>
	{"type": "Feature", "id":<?=$key?>, "geometry": {"type": "Point", "coordinates": [<?=$arItem['PROPERTIES']['MAP']['VALUE']?>]}, "properties": {"balloonContentHeader": "<?=$arItem['NAME']?>", "balloonContentBody": "<?=$contentBaloon?>", "balloonContentFooter": "", "clusterCaption": "<strong><?=$arItem['NAME']?></strong>", "hintContent": "<strong>Текст  <s>подсказки</s></strong>"}},
<?endforeach?>
	]
}

ymaps.ready(init);

function init () {
    var myMap = new ymaps.Map('fullmap', {
            center: [55.76, 37.64],
            zoom: 10
        }, {
            searchControlProvider: 'yandex#search'
        }),
        objectManager = new ymaps.ObjectManager({
            // Чтобы метки начали кластеризоваться, выставляем опцию.
            clusterize: true,
            // ObjectManager принимает те же опции, что и кластеризатор.
            gridSize: 32,
            clusterDisableClickZoom: true
        });

    // Чтобы задать опции одиночным объектам и кластерам,
    // обратимся к дочерним коллекциям ObjectManager.
    objectManager.objects.options.set('preset', 'islands#redDotIcon');
    objectManager.clusters.options.set('preset', 'islands#redClusterIcons');
    myMap.geoObjects.add(objectManager);

    objectManager.add(data);

     myMap.setBounds(myMap.geoObjects.getBounds());

}
</script>
