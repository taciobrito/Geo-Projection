<?php
$conexao = mysqli_connect("localhost", "root", "123456", "desastres");
mysqli_query($conexao, "SET NAMES 'utf8'");
mysqli_query($conexao, 'SET character_set_connection=utf8');
mysqli_query($conexao, 'SET character_set_client=utf8');
mysqli_query($conexao, 'SET character_set_results=utf8');

$sql_desastres = "SELECT * FROM des_mapa_desastre";
$query = mysqli_query($conexao, $sql_desastres);

$string = "id\tid_municipio\tmunicipio\tuf\testado\tmapas_desastres\ttipo_desastre\tvigencia\tibge";

while ($estado = mysqli_fetch_assoc($query)){
	$string .= "\n";
	foreach ($estado as $key => $value) {
		$string .= $value . "\t";
	}
	/*echo "\n".$estado['id']."\t".$estado['id_municipio']."\t".$estado['municipio']
		."\t".$estado['uf']."\t".$estado['estado']."\t".$estado['mapas_desastres']
		."\t".$estado['tipo_desastre']."\t".$estado['vigencia']."\t".$estado['ibge'];*/
}

echo $string;
?>