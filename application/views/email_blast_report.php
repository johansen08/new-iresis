<div class="row">
    <div class="col-md-12">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h3 class="panel-title"><strong>Report </strong></h3>
            </div>

            <div class="panel-body">
                <form class="form-horizontal" >
                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Code</label>
                        <div class="col-md-2 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= $email_blast['code'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Subject</label>
                        <div class="col-md-6 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= $email_blast['subject'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Created By</label>
                        <div class="col-md-2 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= $email_blast['created_by_name'] ?>" require />
                        </div>

                        <label class="col-md-2 col-xs-12 control-label">Created Date</label>
                        <div class="col-md-2 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= $email_blast['created_date'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label">Published On</label>
                        <div class="col-md-2 col-xs-12">
                            <input type="text" name="username" class="form-control" value="<?= $email_blast['publish_date'] ?>" require />
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 col-xs-12 control-label"></label>
                        <div class="col-md-5 col-xs-12">
                            <div id="chart-9" style="height: 300px;"><svg></svg></div>
                        </div>
                    </div>
                    
                </form>

            </div>

            <div class="panel-footer">
                <a href="email_blast" type="reset" class="btn btn-info pull-right link">Cancel</a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var nvd3Charts = function() {
	
        var myColors = ["#ffcc00", "#e8e8e8", "#00BFDD", "#DA3610","#8DCA35","#FF702A","#33414E",
                        "#80CDC2","#A6D969","#D9EF8B","#FFFF99","#F7EC37","#F46D43",
                        "#E08215","#D73026","#A12235","#8C510A","#14514B","#4D9220",
                        "#542688", "#4575B4", "#74ACD1", "#B8E1DE", "#FEE0B6","#FDB863",                                                
                        "#C51B7D","#DE77AE","#EDD3F2"];
        d3.scale.myColors = function() {
            return d3.scale.ordinal().range(myColors);
        };
    
        var startChart9 = function() {
            // Regular pie chart example
            nv.addGraph(function() {
                var chart = nv.models.pieChart().x(function(d) {
                    return d.label;
                }).y(function(d) {
                    return d.value;
                }).showLabels(true).color(d3.scale.myColors().range());

                d3.select("#chart-9 svg").datum(exampleData()).transition().duration(350).call(chart);

                return chart;
            });

            function exampleData() {
                return [{
                    "label" : "Success",
                    "value" : <?= $email_blast_count['total_success_email'] ?>
                }, {
                    "label" : "Failed",
                    "value" : <?= $email_blast_count['total_failed_email'] ?>
                }, {
                    "label" : "Unsent",
                    "value" : <?= $email_blast_count['total_email'] - ($email_blast_count['total_success_email'] + $email_blast_count['total_failed_email']) ?>
                },];
            }

        };

        return {
            init : function() {
                startChart9();
            }
        };
    }();

    nvd3Charts.init();
});
</script>