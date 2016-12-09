<?php
$conexao = mysqli_connect("localhost", "root", "", "desastres");
mysqli_query($conexao, "SET NAMES 'utf8'");
mysqli_query($conexao, 'SET character_set_connection=utf8');
mysqli_query($conexao, 'SET character_set_client=utf8');
mysqli_query($conexao, 'SET character_set_results=utf8');

$sql_desastres = "SELECT * FROM des_mapa_desastre";
$query = mysqli_query($conexao, $sql_desastres);


echo 'id	id_municipio	municipio	uf	estado	mapas_desastres	tipo_desastre	vigencia	ibge';

while ($estado = mysqli_fetch_assoc($query)){
	// print_r($estado);die;
	echo '<br/>'.$estado['id'].'	'.$estado['id_municipio'].'	'.$estado['municipio']
		.'	'.$estado['uf'].'	'.$estado['estado'].'	'.$estado['mapas_desastres']
		.'	'.$estado['tipo_desastre'].'	'.$estado['vigencia'].'	'.$estado['ibge'];
}

?>