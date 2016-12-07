<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <title>Teste D3</title>

    <link rel="stylesheet" type="text/css" href="css/estilo.css" />
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css" />

     <style type="text/css">
        svg{
          background-color: #ddd;
        }

        circle{
          fill: #000;
        }
     </style>
      
</head>
<body>

<?php 
  $conexao = mysqli_connect("localhost", "root", "", "desastres");
  $sql_estados = "SELECT * FROM jos_uf order by sigla_uf ASC";
  $query = mysqli_query($conexao, $sql_estados);

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
        // Define altura e largura da área de trabalho
        var width = 900, height = 500;
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
          if (error) return console.error(error); // verifica a existência de erros
              
          // Recupera as informações dos arquivos passados
          var municipios = topojson.feature(shp, shp.objects[mapa]);
          var dados = topojson.mesh(shp, shp.objects[mapa]);
          
          var scale = 500,
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
                if(mapa == "municipios"){
                  cor = colorirMapa(map.get(d.properties.nome.toUpperCase()));
                } else { 
                  cor = colorirMapa(map.get(d.properties.NM_MUNICIP)); 
                }
                return cor == undefined ? '#fff' : cor; })
              .attr("d", path)
              .on("mouseover", function(d){
                d3.select(this)
                    .style("fill", "#fff")
                    .style("stroke-width", "0.8px")
                              /*var mouse = d3.mouse(g.node()).map( function(d) { return parseInt(d); } );
                              var left = (mouse[0]+20);
                              var top = (mouse[1]+75);

                              //console.log(mouse);

                              tooltip.classed("hidden", false)
                              .style("left", (left) + "px")
                              .style("top", (top - 25) + "px")
                              //.attr("style", "left:"+(left)+"px;top:"+(top-25)+"px")
                              .html(d.properties.nome);
                              //console.log(d.properties.nome);*/
              })
              .on("mouseout", function(d){
                d3.select(this)
                  .style("fill", function(d){ 
                    if(mapa == "municipios"){                      
                      cor = colorirMapa(map.get(d.properties.nome.toUpperCase()));
                    } else { 
                      cor = colorirMapa(map.get(d.properties.NM_MUNICIP)); 
                    }
                    return cor == undefined ? '#fff' : cor; })
                  .style("stroke-width", "0.2px");
                g.select("tooltip").classed("hidden", true);
              })
              .on("click", clicked);

            var tooltip = g.append("div").attr("class", "tooltip hidden");
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