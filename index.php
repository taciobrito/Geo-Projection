<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Teste D3 - Geo-Projection</title>

    <link rel="stylesheet" type="text/css" href="css/estilo.css" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />

     <style type="text/css">
        svg{
          background-color: #10262d;
        }
     </style>
      
</head>
<body>

<script type="text/javascript">var svg, projection, path, g, transform, cor, map, zoom, mapa;</script>

<?php 
  $conexao = mysqli_connect("localhost", "root", "123456", "desastres");
  mysqli_query($conexao, "SET NAMES 'utf8'");
  mysqli_query($conexao, 'SET character_set_connection=utf8');
  mysqli_query($conexao, 'SET character_set_client=utf8');
  mysqli_query($conexao, 'SET character_set_results=utf8');

  $sql_estados = "SELECT * FROM des_recursos_uf order by uf ASC";
  $query = mysqli_query($conexao, $sql_estados);
?>

  <div class="painel-seleciona">
    <label>Selecione o Mapa:</label>
    <select class="seleciona mapa">
      <option value="municipios" selected>Todos</option>
        
    </select>

    <label>Selecione UF:</label>
    <select class="seleciona uf">
      <option value="municipios" selected>Todos</option>
        <?php while ($estado = mysqli_fetch_assoc($query)){ ?>
          <option value="<?php echo $estado['uf']; ?>"><?php echo $estado['uf']; ?></option>
        <?php } ?>
    </select>

    <script> if(mapa != "municipios"){
        
    } else {$(".municip").hide();}
    </script>
      <label>Selecione Município:</label>
      <select class="seleciona municip">
        <option value="municipios" selected>Todos</option>
          
      </select>
  </div>

    <script src="js/jquery.min.js"></script>
  	<script src="js/d3.v4.min.js"></script>
  	<script src="js/d3-queue.v2.min.js"></script>
  	<script src="js/topojson.v2.min.js"></script>
    <script>

        // Define altura e largura da área de trabalho
        var width = $(window).width(), height = 550;

        // variáveis globais
        var svg, projection, path, g, transform, cor, map, zoom;
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

          map = d3.map();
        } // FIM criaSVG

        // adiciona os arquivos externos e os passa para função que carrega o mapa
        function chamaQueue(){
          d3.queue()
            .defer(d3.json, "maps/"+mapa+".json")
            .defer(d3.json, "maps/brasil.json")
            .defer(d3.tsv, "dados.php", function(d) {
              map.set(d.ibge, d);
            })
            .await(carregarmapa);
        } // FIM chamaQueue

        // Cria a projeção do mapa
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
            
            // Função que retorna o nome do municipio formatado
            function codigo_municipio(d){
                return (mapa == "municipios") ? d.properties.id.substring(0, 6) : d.properties.CD_GEOCODM.substring(0, 6);
            }

            // Função que retorna o nome do municipio formatado
            function nome_municipio(d){
                return (mapa == "municipios") ? d.properties.nome.toUpperCase() : d.properties.NM_MUNICIP;
            }

            // Função para colorir o mapa
            function colorirMapa(desastre){
                switch(desastre){
                    case "seca": cor = "#ff8c00";
                      break;
                    case "chuva": cor = "#0000ff";
                      break;
                    case "outros": cor = "#228b22";
                      break;
                    case "stroke": cor = "#e6e6e6";
                      break;
                    default: cor = "#d7d4d4";
                }
                return cor;
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
                    var a = map.get(codigo_municipio(d));
                    cor = colorirMapa(a == undefined ? "" : a.mapas_desastres);
                    return cor; })
                .style("stroke", function(d){
                    var a = map.get(codigo_municipio(d));
                    cor = colorirMapa(a == undefined ? "stroke" : a.mapas_desastres);
                    return cor; })
                .style("stroke-opacity", function(d){
                    var a = map.get(codigo_municipio(d));
                    var opacidade = a == undefined ? "1" : "0.2";
                    return opacidade; })
                .attr("d", path)
                .on("mouseover", function(d){
                    d3.select(this)
                      .style("fill", function(d){
                        var a = map.get(codigo_municipio(d));
                        cor = colorirMapa(a == undefined ? "" : a.mapas_desastres);
                        return cor; })
                      .style("stroke-width", "1")
                      .style("stroke", "#228b22")
                      .style("stroke-opacity", "1");
                })
                .on("mouseout", function(d){
                    d3.select(this)
                      .style("fill", function(d){
                          var a = map.get(codigo_municipio(d));
                          cor = colorirMapa(a == undefined ? "" : a.mapas_desastres);
                          return cor; })
                      .style("stroke-width", "")
                      .style("stroke", function(d){
                          var a = map.get(codigo_municipio(d));
                          cor = colorirMapa(a == undefined ? "stroke" : a.mapas_desastres);
                          return cor; })
                      .style("stroke-opacity", function(d){
                          var a = map.get(codigo_municipio(d));
                          var opacidade = a == undefined ? "1" : "0.2";
                          return opacidade; });

                    tooltip.classed("aparece_muni", true);
                })
                .on("mousemove", function(d){
                    var mouse = d3.mouse(svg.node()).map( function(d) { return parseInt(d); } );
                    var left = (mouse[0]+20);
                    var top = (mouse[1]+50);

                    var desastre = "", tipo_desastre = "", vigencia = "";
                    var a = map.get(codigo_municipio(d));
                        if(a != undefined){
                            desastre = "<br />TIPO DE DESASTRE: " + a.tipo_desastre.toUpperCase();
                            tipo_desastre = "<br />DESASTRE: " + a.mapas_desastres.toUpperCase();
                            
                            var date = new Date(a.vigencia);
                            vigencia = "<br />VIGÊNCIA: " + date.getDate() + '/' + (date.getMonth()+1) + '/' + date.getFullYear();
                        }

                    tooltip.classed("aparece_muni", false)
                        .attr("style", "left:"+(left)+"px;top:"+(top-25)+"px")
                        .html(nome_municipio(d) + tipo_desastre + desastre + vigencia);
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
            g.style("stroke-width", 1 / d3.event.transform.k + "px");
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

        // Cria div do tooltipo
        var tooltip = d3.select("body").append("div").attr("class", "info_muni aparece_muni");

        // Cria a área de trabalho svg
        criaSvg();

        // Troca o mapa de acordo com o selecionado no combo
        mapa = $('.uf').val();
        $('.uf').change(function(){
          mapa = $('.uf').val().toLowerCase();
          $('svg').remove();
          criaSvg();
          chamaQueue();
        });

        // Chama a função que envia os dados
        chamaQueue();

   </script>

  <script src="js/bootstrap.min.js"></script>

</body>

</html>