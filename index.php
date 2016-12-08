<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Teste D3</title>

    <link rel="stylesheet" type="text/css" href="css/estilo.css" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />

     <style type="text/css">
        svg{
          background-color: #a3ccff;
        }
     </style>
      
</head>
<body>

<?php 
  $conexao = mysqli_connect("localhost", "root", "", "desastres");
  $sql_estados = "SELECT * FROM jos_uf order by sigla_uf ASC";
  $query = mysqli_query($conexao, $sql_estados);

  $sql_desastres = "SELECT uf, mapas_desastres, ibge FROM des_mapa_desastre";
  $query2 = mysqli_query($conexao, $sql_desastres);
  
  $i = 0;
  $desastres = array();
  while ($teste = mysqli_fetch_array($query2)) {
    // print_r($teste);
    // array_push($desastres, $teste);
    // print_r($desastres);
    $desastres[$teste['uf']][] = $teste;
    $i++;
  }
  //print_r($desastres);die;

  $desastres_json = json_encode($desastres);
  //echo $desastres_json;
?>

  <div class="painel-seleciona">
    <label>Selecione o Mapa:</label>
    <select class="seleciona">
      <option value="municipios" selected>Todos</option>
        <?php while ($estado = mysqli_fetch_assoc($query)){ ?>
          <option value="<?php echo $estado['sigla_uf']; ?>"><?php echo $estado['sigla_uf']; ?></option>
        <?php } ?>
    </select>
  </div>

    <script src="js/jquery.min.js"></script>
  	<script src="js/d3.v4.min.js"></script>
  	<script src="js/d3-queue.v2.min.js"></script>
  	<script src="js/topojson.v2.min.js"></script>
    <script>
      var desastres1 = '<?php echo $desastres_json; ?>';
      var desastres = JSON.parse(desastres1);
      //console.log(desastres);
      console.log(desastres['AC'][0]['ibge']);

        // Define altura e largura da área de trabalho
        var width = $(window).width(), height = 550;

        // variáveis globais
        var svg, projection, path, g, transform, colorirMapa, map, zoom;
        active = d3.select(null);

        function criaSvg(){
          // cria o elemento svg da área de trabalho
          svg = d3.select("body").append("svg")
             .attr("width", width).attr("height", height)
             .on("click", stopped, true);

          svg.append("rect")
              .attr("class", "background")
              .attr("width", width)
              .attr("height", height)
              .on("click", reset);

          // atribui o svg a outra variável
          g = svg.append("g");
          
          // Define o zoom
          zoom = d3.zoom()
              .scaleExtent([1 , 8])
              .on("zoom", zoomed);
          
          // Faz a chamada do zoom
          svg.call(zoom);
          
          // Define um range de cores para pintar o mapa
          colorirMapa = d3.scaleLinear()
            .domain([0, 20, 40, 60, 80, 100])
            .range(["#ff0000", "#ff8a00", "#ffff00", "#00ff00", "#00f6ff", "#0000ff"]);

            /*$seca = '#FF8C00';
            $chuva = '#0000FF';
            $outros = '#228B22';
            $nao_info = '#D7D4D4';*/

          map = d3.map();
        } // FIM criaSVG

        // adiciona os arquivos externos e os passa para função que carrega o mapa
        function chamaQueue(){
          d3.queue()
            .defer(d3.json, "maps/"+mapa+".json")
            .defer(d3.tsv, "datas/enemcota.tsv", function(d) {
              map.set(d.municipio, d.percentual);
            })
            .await(carregarmapa);
        } // FIM chamaQueue

        function criaProjecao(scale, center, offset){
            // Define o tipo de projeção
            projection = d3.geoMercator()
                .scale(scale)
                .center(center)
                .translate(offset);

            // Cria o path da projeção
            path = d3.geoPath().projection(projection);
        }

        //função responsável por desenhar o mapa
        function carregarmapa(error, shp) {
            
            function nome_municipio(d){
                return (mapa == "municipios") ? d.properties.nome.toUpperCase() : d.properties.NM_MUNICIP;
            }

            if (error) return console.error(error); // verifica a existência de erros
                
            // Recupera as informações dos arquivos passados
            var municipios = topojson.feature(shp, shp.objects[mapa]);
            var dados = topojson.mesh(shp, shp.objects[mapa]);
            
            var scale = 600,
                center = d3.geoCentroid(municipios),
                offset = [width / 2, height / 2];

            // Chama a função que cria projeção
            criaProjecao(scale, center, offset);

            var bounds = path.bounds(municipios),
                scaleX = scale * width / (bounds[1][0] - bounds[0][0]),
                scaleY = scale * height / (bounds[1][1] - bounds[0][1]),
                scale = (scaleX < scaleY) ? scaleX : scaleY,
                offset = [(width - (bounds[0][0] + bounds[1][0]) / 2), (height - (bounds[0][1] + bounds[1][1]) / 2)];

            // Chama NOVAMENTE a função que cria projeção
            criaProjecao(scale, center, offset);

            // projeta o mapa e o pinta
            g.selectAll(".municipios")
                .data(municipios.features)              
                .enter().append("path")
                .attr("class", "municipios")
                .style("fill", function(d){
                    cor = colorirMapa(map.get(nome_municipio(d)));
                    return cor == undefined ? "#ffffff" : cor; })
                .attr("d", path)
                .on("mouseover", function(d){
                    d3.select(this)
                      .style("fill", "#ffffff")
                      .style("stroke-width", "1")
                      .style("stroke", "#228B22")
                })
                .on("mouseout", function(d){
                    d3.select(this)
                    .style("fill", function(d){
                        cor = colorirMapa(map.get(nome_municipio(d)));
                        return cor == undefined ? "#ffffff" : cor; })
                    .style("stroke", "#333")
                    .style("stroke-width", "0.2");

                  tooltip.classed("aparece_muni", true);
                })
                .on("mousemove", function(d,i){
                    var mouse = d3.mouse(svg.node()).map( function(d) { return parseInt(d); } );
                    var left = (mouse[0]+20);
                    var top = (mouse[1]+50);
                    
                    tooltip.classed("aparece_muni", false)
                    .attr("style", "left:"+(left)+"px;top:"+(top-25)+"px")
                    .html(nome_municipio(d)+"<br />"+map.get(nome_municipio(d))+"%");//+"<br />Total: "+map.get(d.total)+"<br />Porcentagem: "+map.get(d.percentual)+"%");
                })
                .on("click", clicked);

        } //FIM carregamapa

        function clicked(d) {
            if (active.node() === this) return reset();
            active.classed("active", false);
            active = d3.select(this).classed("active", true);

            var bounds = path.bounds(d),
                dx = bounds[1][0] - bounds[0][0],
                dy = bounds[1][1] - bounds[0][1],
                x = (bounds[0][0] + bounds[1][0]) / 2,
                y = (bounds[0][1] + bounds[1][1]) / 2,
                scale = Math.max(1, Math.min(8, 0.9 / Math.max(dx / width, dy / height))),
                translate = [width / 2 - scale * x, height / 2 - scale * y];

            svg.transition()
                .duration(750)
                .call( zoom.transform, d3.zoomIdentity.translate(translate[0],translate[1]).scale(scale) );
        }

        function reset() {
            active.classed("active", false);
            active = d3.select(null);

            svg.transition()
                .duration(750)
                .call( zoom.transform, d3.zoomIdentity );
        }

        // função de zoom
        function zoomed() {
            g.style("stroke-width", 1.5 / d3.event.transform.k + "px");
            g.attr("transform", d3.event.transform);
        } // FIM zoomed

        function stopped() {
          if (d3.event.defaultPrevented) d3.event.stopPropagation();
        }

        // Configuração de Redimensionamento
        function redimensionar(){
            width = $(window).width();
        }

        window.addEventListener("orientationchange", function () {
            redimensionar();
        }, false);

        window.addEventListener("resize", function () {
            redimensionar();
        }, false);

        $(document).on('ready', function () {
            redimensionar();
        }); // FIM configurações do redimensionamento

        var tooltip = d3.select("body").append("div").attr("class", "info_muni aparece_muni");

        criaSvg();

        var mapa = $('.seleciona').val();
        $('.seleciona').change(function(){
          mapa = $('.seleciona').val().toLowerCase();
          $('svg').remove();
          criaSvg();
          chamaQueue();
        });

        chamaQueue();

   </script>

  <script src="js/bootstrap.min.js"></script>

</body>

</html>