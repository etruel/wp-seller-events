				var table_simple = "";
				function generate(myid) {

				  var doc = new jsPDF('p', 'pt');
				  var res = doc.autoTableHtmlToJson(document.getElementById(myid));
				 // doc.autoTable(res.columns, res.data, {margin: {top: 80}});

				  var header = function(data) {
				    doc.setFontSize(18);
				    doc.setTextColor(40);
				    doc.setFontStyle('normal');
				    //doc.addImage(headerImgData, 'JPEG', data.settings.margin.left, 20, 50, 50);
				    doc.text("Listado de Eventos", data.settings.margin.left, 50);
				  };

				  function footer(){ 
				  	 	doc.setFontSize(10);
				    	doc.setTextColor(40);
					    doc.text(40,800, 'WP-Seller Events by etruel.com'); //print number bottom right
					    doc.page ++;
				  };

				  var options = {
				    beforePageContent: header,
				    margin: {
				      top: 80
				    },
				    startY: /*doc.autoTableEndPosY() + 80*/ 80
				  };

				  doc.autoTable(res.columns, res.data, options);
				  footer();
				  //doc.save("table.pdf");
				  doc.output("dataurlnewwindow");

				}
				jQuery(document).ready(function(){
					jQuery(document).on('click','#printButtonPDF',function(){
						printtable = jQuery(".wp-list-table").clone();
						printtable.css({'display':'none'});
						printtable.attr("id","wp-list-table-clone");
						printtable.find('thead tr td').remove();
						printtable.find('tbody tr th').remove();
						printtable.find('tbody tr td div').remove();
						printtable.find('tbody tr td button').remove();
						printtable.find('tfoot').remove();
						jQuery('body').append(printtable);
						generate(printtable.attr("id"));
						printtable.remove();

					});
				});