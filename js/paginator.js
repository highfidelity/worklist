(function( $ ) {
    $.fn.paginate = function(numberofrowstodisplayperpage, animationspeed) {
        var tableid = this.attr("id");
        new TablePaginator(tableid, numberofrowstodisplayperpage, animationspeed)
    };
})( jQuery );

function TablePaginator(tableid, numberofrowstodisplayperpage, animationspeed) {
    this.pagenum = 1;
    this.sizeoftable = $("#" + tableid + " tr").length - 1;
    this.count = 1;
    var self = this;

    this.resetRows = function(start, end) {
       $("#" + tableid + " tr, #" + tableid + "pagenumholder").not("tr:first").hide();
       this.displayRows(start, end);
    }

    this.displayRows = function(start, end) {
        if(start <= end) {
            $("#" + tableid + start).fadeIn(animationspeed);
            if (start == end || start == this.sizeoftable) {
                $("#" + tableid + "pagenumholder").show();
            } else {
                start++;
                self.displayRows(start,end);
            }
        }
    }

    this.createPagesandPageholder = function() {
        var beginning,
            ending,
            current;

        if(this.sizeoftable <= numberofrowstodisplayperpage) {
            beginning = 1;
            end = this.sizeoftable;
        } else {
            $("#" + tableid).after('<div style="margin-top:5px; text-align: center;" id="'
                                  + tableid + 'pagenumholder" ><span id="'
                                  + tableid + 'beginning" title="First"> <<&nbsp  </span><span id="'
                                  + tableid + 'rewind" title="Prev">  &nbsp<&nbsp  </span><input style="width: 15px; border: none;" id="'
                                  + tableid + 'currentPage" value="1"/> of <div style="display: inline-block; margin: 3px; width: inherit;" id="'
                                  + tableid + 'numOfPage"></div><span id="'+tableid+'forward" title="Next">  &nbsp>&nbsp  </span><span id="'
                                  + tableid + 'end" title="Last">  &nbsp>>&nbsp  </span></div>');
            var numberofpages = Math.ceil(this.sizeoftable/numberofrowstodisplayperpage);

            $("#" + tableid + "numOfPage").text(numberofpages);
            $("#" + tableid + "pagenumholder").hide();
            $( "#" + tableid + "beginning" ).css("cursor", "pointer");
            $( "#" + tableid + "rewind" ).css("cursor", "pointer");
            $( "#"+tableid+"forward" ).css("cursor", "pointer");
            $( "#" + tableid + "end" ).css("cursor", "pointer");
            $( "#" + tableid + "beginning" ).click(function() {
                $("#" + tableid + "currentPage").val(1);
                current = 1;
                ending = numberofrowstodisplayperpage * current;
                beginning = (ending - numberofrowstodisplayperpage) + 1;
                self.resetRows(beginning, ending);
            });

            $( "#" + tableid + "rewind" ).click(function() {
                current = $("#" + tableid + "currentPage").val();
                if(current != 1 && current >= 1 && current <= numberofpages) {
                    current--;
                    $("#" + tableid + "currentPage").val(current);
                    ending = numberofrowstodisplayperpage * current;
                    beginning = (ending - numberofrowstodisplayperpage) + 1;
                    self.resetRows(beginning, ending);
                }
            });

            $( "#" + tableid + "forward" ).click(function() {
                current = $("#" + tableid + "currentPage").val();
                if(current != numberofpages && current >= 1 && current <= numberofpages) {
                    current++;
                    $("#" + tableid + "currentPage").val(current);
                    ending = numberofrowstodisplayperpage * current;
                    beginning = (ending - numberofrowstodisplayperpage) + 1;
                    self.resetRows(beginning, ending);
                }
            });

            $( "#" + tableid + "end" ).click(function() {
                $("#" + tableid + "currentPage").val(numberofpages);
                current = numberofpages;
                ending = numberofrowstodisplayperpage * current;
                beginning = (ending - numberofrowstodisplayperpage) + 1;
                self.resetRows(beginning, ending);
            });

            $("#" + tableid + "currentPage").change(function() {
                current = $("#" + tableid + "currentPage").val();
                if(current >= 1 && current <= numberofpages) {
                    ending = numberofrowstodisplayperpage * current;
                    beginning = (ending - numberofrowstodisplayperpage) + 1;
                    self.resetRows(beginning, ending);
                }
            })
        }
    }

    this.construct = function() {
        var counter = 0;
        $("#" + tableid + " tr").not("tr:first").hide();
        $("#" + tableid + " tr").each(function(){$(this).attr("id", tableid + counter); counter++; });
        this.createPagesandPageholder();
        this.displayRows(1, numberofrowstodisplayperpage);
    }
    this.construct();
}
