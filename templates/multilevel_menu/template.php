<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?//print_r($arResult);?>

<style>
	.main-menu {
		float:left;
		width: calc(100% - 250px);
		margin-bottom:0px;
	}

	.main-menu li {
		float:left;
		list-style:none;
		position:relative;
		margin-right:30px;
	}

	.main-menu a {
		color: #212121;
		display:block;
		text-decoration: none;
   		letter-spacing: 0.1em;
		line-height: 60px;
	}

	.main-menu a:hover {
		color: #DD0000;
	}

	.main-menu li ul {
		position:absolute;
		width:300px;
		top:60px;
		left:0px;
		display:none;
		background:#fff;
		padding:15px;
		box-shadow:0px 10px 20px #dedede;
	}

	.main-menu li ul li {
		width:100%;
		margin-bottom:15px;
	}

	.main-menu li ul li a {
		line-height:20px;
	}

	.main-menu li:hover ul {
		display:block;
	}
</style>

<?if (!empty($arResult)):?>

<?
	$arMenu = array();
	$parent_key = 0;
	foreach($arResult as $key => $arItem) {
		if ($arItem['DEPTH_LEVEL'] == 1) {
			$arMenu[$key] = $arItem;
		} else {
			$arMenu[$parent_key]['SUBMENU'][] = $arItem;
		}

		if ($arItem['IS_PARENT'] == 1) {
			$parent_key = $key;
		}
	}
	//print_r($arMenu);
?>

<ul class="main-menu">

<?foreach($arMenu as $arItem):?>
	<li>
		<a href="<?=$arItem['LINK']?>"><?=$arItem['TEXT']?></a>
		<?if(count($arItem['SUBMENU']) > 0):?>
			<ul>
			<?foreach($arItem['SUBMENU'] as $arSubItem):?>
				<li><a href="<?=$arSubItem['LINK']?>"><?=$arSubItem['TEXT']?></a></li>
			<?endforeach?>
			</ul>
		<?endif?>
	</li>
<?endforeach?>

</ul>
<?endif?>