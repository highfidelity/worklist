var TimelineChart;
var LoveChart;

(function () {

function interpolate(values, x) {
    var i = (values.length - 1) * x;
    var i1 = Math.floor(i), i2 = Math.ceil(i), k = i - i1;
    return values[i1] + (values[i2] - values[i1]) * k;
};

function Series(chart, attrs) {
    return {
        attrs:attrs,
        chart:chart,
        stroke:chart.r.path('M0,0').attr({ fill:'none' }).attr(attrs),
        fill:chart.r.path('M0,0').attr({ stroke:'none' }).attr(attrs),
        hasData:false,
        values:undefined,
        interpolated:undefined,
        verticals:[],
        dots:[],
        dotSize:5,
        bigDotSize:7,
        maxSamples:30,

        y:function (v) {
            return this.chart.chartTop + Math.round((1 - v) * this.chart.chartHeight);
        },

        yFromX:function (x) {
            return this.y(interpolate(this.interpolated, (x - this.chart.chartLeft) / this.chart.chartWidth));
        },

        findNearestPoint:function (x) {
            var result = undefined, dx = undefined;

            if(this.interpolated) {
	      for (var i = 0; i < this.interpolated.length; i++) {
		  if (typeof this.interpolated[i] !== 'undefined') {
		      var z = this.chart.chartLeft + this.chart.chartWidth * i / (this.interpolated.length - 1);
		      var t = Math.abs(x - z);
		      if (typeof dx === 'undefined' || t < dx) {
			  dx = t;
			  result = { x:z, y:this.y(this.interpolated[i]) };
		      }
		  }
	      }
	    }

            return result;
        },

        generatePath:function () {
            var dx = this.chart.chartWidth / (this.interpolated.length - 1), px = -dx, py = 0;
            var path = '';
            if (isNaN(dx)) return null;

            for (var i = 0; i < this.interpolated.length; i++) {
                var x = this.chart.chartLeft + this.chart.chartWidth * i / (this.interpolated.length - 1);
                if (isNaN(x)) return null;
                if (typeof this.interpolated[i] !== 'undefined') {
                    var y = this.y(this.interpolated[i]);
                    path += i ? 'C' + [ x - (x - px) / 2, py, x - (x - px) / 2, y, x, y ] : 'M' + [ x, y ];
                    py = y;
                    px = x;
                }
            }

            /*if (i > this.maxSamples) {
                this.maxSamples = i;
            } else {
                for (; i < this.maxSamples; i++) {
                    path += 'L' + [ x, y ];
                }
            }*/

            return path;
        },

        drawStroke:function () {
            var path = this.generatePath();
            var stroke = { path:path };

            if (!this.hasData) {
                this.stroke.attr(stroke);
                this.hasData = true;
            } else {
                this.stroke.animate(stroke, this.chart.animationLength, '<>');
            }
        },

        drawFill:function () {
            var path = this.generatePath();

            var fill = { path:path + 'L' + (this.chart.chartLeft + this.chart.chartWidth) + ',' + (this.chart.chartTop + this.chart.chartHeight) + ' ' + this.chart.chartLeft + ',' + (this.chart.chartTop + this.chart.chartHeight) + 'z' };

            if (!this.hasData) {
                this.fill.attr(fill);
                this.hasData = true;
            } else {
                var self = this;
                this.fill.animate({ opacity:0 }, this.chart.animationLength / 2, '<>', function () {
                    self.fill.attr(fill);
                    self.fill.animate({ opacity:attrs.opacity || 1 }, self.chart.animationLength / 2, '<>');
                });
            }
        },

        drawDots:function () {
            var dx = this.chart.chartWidth / (this.interpolated.length - 1);

            for (var i = 0, o = 0; i < this.interpolated.length; i++) {
                if (typeof this.interpolated[i] === 'undefined') {
                    continue;
                }

                var x = this.chart.chartLeft + this.chart.chartWidth * i / (this.interpolated.length - 1), y = this.y(this.interpolated[i]);
                if (!this.dots[o]) {
                    this.dots[o] = this.chart.r.circle(this.chart.width + this.dotSize, y, this.dotSize).attr(this.attrs);
                }

                this.dots[o].show().animate({ cx:x, cy:y }, this.chart.animationLength, '<>');

                o++;
            }

            if (o > this.maxSamples) {
                this.maxSamples = o;
            }

            for (; o < this.dots.length; o++) {
                if (this.dots[o]) {
                    this.dots[o].show().animate({ cx:this.chart.width + this.dotSize, cy:y }, this.chart.animationLength, '<>');
                }
            }
        },

        drawVerticals:function () {
            var dx = this.chart.chartWidth / (this.interpolated.length - 1);

            for (var i = 0, o = 0; i < this.interpolated.length; i++) {
                if (typeof this.interpolated[i] === 'undefined') {
                    continue;
                }

                var x = this.chart.chartLeft + this.chart.chartWidth * i / (this.interpolated.length - 1), y = this.y(this.interpolated[i]);
                if (isNaN(x) ) return;
                if (!this.verticals[o]) {
                    this.verticals[o] = this.chart.r.path('M' + this.chart.width + ',' + this.chart.chartTop + ' L' + this.chart.width + ',' + (this.chart.chartTop + this.chart.chartHeight)).attr(this.attrs);
                }
                this.verticals[o].animate({ path:'M' + x + ',' + this.chart.chartTop + ' L' + x + ',' + (this.chart.chartTop + this.chart.chartHeight) }, this.chart.animationLength, '<>');

                o++;
            }

            if (o > this.maxSamples) {
                this.maxSamples = o;
            }

            for (; o < this.verticals.length; o++) {
                if (this.verticals[o]) {
                    this.verticals[o].animate({ path:'M' + this.chart.width + ',' + this.chart.chartTop + ' L' + this.chart.width + ',' + (this.chart.chartTop + this.chart.chartHeight) });
                }
            }
        },

        draw:undefined,

        setData:function (values, dontInterpolate) {
            this.values = values;
            this.interpolated = !dontInterpolate && values.length > this.maxSamples ? this.chart.interpolate(values, this.maxSamples) : values;
            this.draw();
        },

        setRandomData:function () {
            var values = [], o = Math.random() * 100;

            for (var i = 0; i < 30; i++) {
                values.push(Math.random() * 0.2 + 0.8 * (Math.sin(o + i / 3) / 2 + 0.5));
            }

            this.setData(values);
        }
    };
}

TimelineChart = function (containerId, width, height, samples) {
    this.r = Raphael(containerId, width, height);
    this.base = this.r.rect(0, 0, width, height).attr({ fill:'#FFF', stroke:'none' });
    this.tmpObjects = [];
    this.width = width;
    this.height = height;
    this.samples = samples;
    this.series = [];
    this.scale = 1.0;
    this.animationLength = 2000;
    this.popupTextCallback = undefined;
    this.dotMouseoverCallback = undefined;
    this.dotMouseoutCallback = undefined;
    this.frameVisible = false;
    this.frame = undefined;
    this.labels = [];
    this.axisLabelsAreaHeight = undefined;
    this.padding = 10;
    this.chartLeft = this.padding + 30;
    this.chartTop = this.padding;
    this.chartRight = this.padding + 30;
    this.chartWidth = width - this.padding - this.chartLeft - this.chartRight;
    this.chartHeight = undefined;
    this.leftLabels = [];
    this.rightLabels = [];
    this.bottomLabels = [];
    this.grid = [];
    this.gridRows = 1;
    this.gridCols = 1;
    this.gridColor = '#E0E0E0';

    this.vGridLine = function (x) {
        return this.r.path('M' + x + ',' + this.chartTop + ' L' + x + ',' + (this.chartTop + this.chartHeight)).attr({ stroke:this.gridColor }).toBack();
    };

    this.hGridLine = function (y) {
        return this.r.path('M' + this.chartLeft + ',' + y + ' L' + (this.chartLeft + this.chartWidth) + ',' + y).attr({ stroke:this.gridColor }).toBack();
    };

    this.setAxisLabelsAreaHeight = function (v) {
        this.axisLabelsAreaHeight = v;
        this.chartHeight = this.height - this.chartTop - v;

        for (var i = 0; i < this.grid.length; i++) {
            this.grid[i].remove();
        }

        this.grid = [];

        for (var i = 0; i <= this.gridRows; i++) {
            this.grid.push(this.hGridLine(this.chartTop + this.chartHeight / this.gridRows * i));
        }

        for (var i = 0; i <= this.gridCols; i++) {
            this.grid.push(this.vGridLine(this.chartLeft + this.chartWidth / this.gridCols * i));
        }
    };

    this.setAxisLabelsAreaHeight(0);
};

TimelineChart.prototype.interpolate = function (v, samples) {
    if (!samples) {
        samples = this.samples;
    }

    var r = [ samples ];

    for (var i = 0; i < samples; i++) {
        r[i] = interpolate(v, i / (samples - 1));
    }

    return r;
};

TimelineChart.prototype.addVerticalsSeries = function (attrs) {
    var result = new Series(this, attrs);
    result.draw = result.drawVerticals;
    this.series.push(result);
    return result;
};

TimelineChart.prototype.addDotsSeries = function (attrs) {
    var result = new Series(this, attrs);
    result.draw = result.drawDots;
    this.series.push(result);
    return result;
};

TimelineChart.prototype.addStrokeSeries = function (attrs) {
    var result = new Series(this, attrs);
    result.draw = result.drawStroke;
    this.series.push(result);
    return result;
};

TimelineChart.prototype.addFillSeries = function (attrs) {
    var result = new Series(this, attrs);
    result.draw = result.drawFill;
    this.series.push(result);
    return result;
};

TimelineChart.prototype.forAllSeries = function (callback) {
    for (var i = 0; i < this.series.length; i++) {
        callback.call(this, this.series[i], i);
    }
};

TimelineChart.prototype.showFrameAt = function (frameX, frameY, lines) {
    if (this.frameVisible) {
        this.hideFrame();
    }

    var hPadding = 10, vPadding = 10, vOffset = 10, hOffset = 10;

    if (!this.frame) {
        this.frame = this.r.rect(0, 0, 0, 0, 5).attr({ fill:'#FFF', stroke:'#B2B9B2', 'stroke-width':2 } );
    }

    this.tmpObjects.push(this.r.circle(frameX, frameY, 7).attr(this.framePointAttr || {}).toFront());

    var maxWidth = 0, totalHeight = 0;

    for (var i = 0; i < lines.length; i++) {
        var l = this.r.text(0, 0, '').attr(lines[i]).attr({ 'text-anchor':'start' });
        this.labels.push(l);
        maxWidth = Math.max(maxWidth, l.getBBox().width);
        totalHeight += l.getBBox().height;
    }

    var frameWidth = hPadding * 2 + maxWidth, frameHeight = vPadding * 2 + totalHeight;

    frameX += hOffset;

    if (frameX + frameWidth > this.width - 1) {
        frameX -= hOffset * 2 + frameWidth;
    }

    frameY += vOffset;

    if (frameY + frameHeight > this.chartHeight - 1) {
        frameY -= vOffset * 2 + frameHeight;
    }

    this.frame.attr({ x:frameX, y:frameY, width:frameWidth, height:frameHeight }).show().toFront();
    var y = frameY + vPadding;
    for (var i = 0; i < this.labels.length; i++) {
        this.labels[i].attr({
            x:frameX + frameWidth / 2 - this.labels[i].getBBox().width / 2,
            y:y + this.labels[i].getBBox().height / 2
        }).show().toFront();

        y += this.labels[i].getBBox().height;
    }

    this.frameVisible = true;
};

TimelineChart.prototype.hideFrame = function () {
    if (this.frameVisible) {
        for (var i = 0; i < this.tmpObjects.length; i++) {
            this.tmpObjects[i].remove();
        }

        this.frame.hide();

        for (var i = 0; i < this.labels.length; i++) {
            this.labels[i].remove();
        }

        this.labels = [];

        this.frameVisible = false;
    }
};

TimelineChart.prototype.showSample = function (x, y) {
    var xs = [];

    this.forAllSeries(function (series, i) {
        xs[i] = Math.round((series.values.length - 1) * (x - this.chartLeft) / (this.chartWidth - 1));
    });

    if (this.popupTextCallback) {
        this.showFrameAt(x, y, this.popupTextCallback(xs));
    }
};

TimelineChart.prototype.hideSample = function () {
    this.hideFrame();
};

TimelineChart.prototype.getSeriesMax = function (seriesData, initialMax) {
	var max = initialMax;
	for (var i = 0; i < seriesData.length; i++) {
	    var dataValue = seriesData[i], intValue = 0;
	    if(dataValue == undefined) {
	      continue;
	    }
	    intValue = parseInt(dataValue,10);
	    max = Math.max(intValue, max);
	}
	return max;
};

TimelineChart.prototype.normalizeSeries = function (seriesData, maxValue) {
	var normalizedData = [];
	for (var i = 0; i < seriesData.length; i++) {
	    var dataValue = seriesData[i], intValue = 0;
	    if(dataValue == undefined) {
	      normalizedData[i] = undefined;
	    } else {
	      intValue = parseInt(dataValue,10);
	      normalizedData[i] = intValue / maxValue;
	    }
	}
	return normalizedData;
};

TimelineChart.prototype.updateAxisMax = function (seriesData, targetSeriesData) {
	var normalizedData = [];
	for (var i = 0; i < seriesData.length; i++) {
	    var dataValue = seriesData[i];
	    if(dataValue == undefined || dataValue <= targetSeriesData[i]) {
	      normalizedData[i] = targetSeriesData[i];
	    } else {
		normalizedData[i] = dataValue;
	    }
	}
	return normalizedData;
};


TimelineChart.prototype.setBottomLabels = function (attrs, labels) {
    this.setAxisLabelsAreaHeight(labels.length * 10 + 10);

    for (var i = 0; i < this.bottomLabels.length; i++) {
        this.bottomLabels[i].remove();
    }

    this.bottomLabels = [];

    for (var i = 0; i < labels.length; i++) {
        var y = this.height - this.axisLabelsAreaHeight + (i + 1) * 10;
        var row = labels[i];

        var minX = -1000, spacing = 3;

        if (row.length > 1) {
            for (var j = 0; j < row.length; j++) {
                if (typeof row[j] !== 'undefined' && row[j] != null) {
                    var x = this.chartLeft + j / (row.length - 1) * this.chartWidth;
                    var t = this.r.text(x, y, '' + row[j]).attr({ 'text-anchor':'middle' }).attr(attrs);
                    if (x - t.getBBox().width / 2 < minX) {
                        t.remove();
                    } else {
                        minX = x + t.getBBox().width / 2 + spacing;
                        this.bottomLabels.push(t);
                    }
                }
            }
        }
    }
};

TimelineChart.prototype.setLeftLabels = function (attrs, max) {
    var steps = [ 5, 10, 20, 50, 100 ], minSpacing = 20;
    var step = steps[0], i = 0;
    while (this.chartHeight * step / max < minSpacing) {
        step = steps[i] ? steps[i] : step * 2;
        i++;
    }

    for (var i = 0; i < this.leftLabels.length; i++) {
        this.leftLabels[i].remove();
    }

    this.leftLabels = [];

    var y = 0;

    while (y <= max) {
        var vy = this.chartTop + this.chartHeight * (1 - y / max);
        this.leftLabels.push(this.r.text(35, vy, '' + y).attr(attrs));
        this.leftLabels.push(this.hGridLine(vy));
        y += step;
    }
};

TimelineChart.prototype.setRightLabels = function (attrs, max) {
    var steps = [ 5, 10, 20, 50, 100 ], minSpacing = 20;
    var step = steps[0], i = 0;
    while (this.chartHeight * step / max < minSpacing) {
        step = steps[i] ? steps[i] : step * 2;
        i++;
    }

    for (var i = 0; i < this.rightLabels.length; i++) {
        this.rightLabels[i].remove();
    }

    this.rightLabels = [];

    var y = 0;

    while (y <= max) {
        var vy = this.chartTop + this.chartHeight * (1 - y / max);
        this.rightLabels.push(this.r.text(this.chartWidth + 45, vy, '' + y).attr(attrs));
        this.rightLabels.push(this.hGridLine(vy));
        y += step;
    }
};

LoveChart = {
    cache:{},
    chart:null,
    messagesFill:null,
    messagesStroke:null,
    messagesDots:null,
    sendersFill:null,
    feeCountFill:null,

    initialize:function (containerId, width, height, samples) {
        this.chart = new TimelineChart(containerId, width, height, samples);

        this.chart.framePointAttr = { fill:'#F4B645', stroke:'#FFF' };
        this.userType = 'sender';
        this.forceWeekly = false;
	this.enableCache = false;

        this.messagesVerticals = this.chart.addVerticalsSeries({ stroke:'#000', opacity:0.1 });
        this.messagesFill = this.chart.addFillSeries({ fill:'#F4B645', opacity:0.3 });
        this.messagesStroke = this.chart.addStrokeSeries({ stroke:'#F4B645', 'stroke-width':3 });
        var dots = this.messagesDots = this.chart.addDotsSeries({ fill:'#F4B645', stroke:'#FFF' });

        this.sendersFill = this.chart.addFillSeries({ fill:'#00F01B', opacity:0.3 });
        this.feeCountFill = this.chart.addFillSeries({ fill:'#00FFFB', opacity:0.3 });

        var to;

        this.chart.r.canvas.onmousemove = function (e) {
            if (to) {
                clearTimeout(to);
                to = undefined;
            }

            var coords = dots.findNearestPoint(e.pageX - $('#' + containerId).offset().left);
            if (typeof coords !== 'undefined') {
                LoveChart.chart.showSample(coords.x, coords.y);
            }
        };

        this.chart.r.canvas.onmouseout = function (e) {
            if (to) {
                clearTimeout(to);
                to = undefined;
            }

            to = setTimeout(function () {
                LoveChart.chart.hideSample();
            }, 500);
        };
    },


    load:function (from, to, username) {
        this.getData(from, to, username, function (data) {
	   var leftMax = LoveChart.chart.getSeriesMax(data.messages,5);
	   var rightMax = LoveChart.chart.getSeriesMax(data.senders,5);
	   rightMax = LoveChart.chart.getSeriesMax(data.feeCount,rightMax);
           var senders = [], messages = [], feeCount = [];

	   messages = LoveChart.chart.normalizeSeries(data.messages, leftMax);
	   senders = LoveChart.chart.normalizeSeries(data.senders, rightMax);
	   feeCount = LoveChart.chart.normalizeSeries(data.feeCount, rightMax);

            var labels = [];
            var fullLabels = data.labels.xFull;
            var month, year;
            var nonEmptyDateLabels = 0, nonEmptyWeekLabels = 0, nonEmptyMonthLabels = 0, nonEmptyYearLabels = 0;
            labels.push(data.labels.x1);
            labels.push(data.labels.x2);

            LoveChart.chart.setBottomLabels({ fill:'black', font:'11px Arial, sans-serif' }, labels);
            LoveChart.chart.setLeftLabels({ fill:'#F4B645', font:'11px Arial, sans-serif', 'text-anchor':'end' }, leftMax, 100);
            LoveChart.chart.setRightLabels({ fill:'#00F01B', font:'11px Arial, sans-serif', 'text-anchor':'start' }, rightMax, 100);

            var messagesFillData = [].concat(messages);
            messagesFillData[0] = messages[0] || 0;
            messagesFillData[messagesFillData.length - 1] = messages[messages.length - 1] || 0;

            var sendersFillData = [].concat(senders);
            sendersFillData[0] = senders[0] || 0;
            sendersFillData[sendersFillData.length - 1] = senders[senders.length - 1] || 0;

            var feeCountFillData = [].concat(feeCount);
            feeCountFillData[0] = feeCount[0] || 0;
            feeCountFillData[feeCountFillData.length - 1] = feeCount[feeCount.length - 1] || 0;
            var range = 'Day';

            var yAxisDots = [].concat(messages);
            yAxisDots = LoveChart.chart.updateAxisMax(sendersFillData,yAxisDots);
            yAxisDots = LoveChart.chart.updateAxisMax(feeCountFillData,yAxisDots);

	    messages[0] = messages[0] || 0;
	    messages[messages.length - 1] = messages[messages.length - 1] || 0;

            LoveChart.messagesVerticals.setData(messages, true);
            LoveChart.messagesFill.setData(messagesFillData, true);
            LoveChart.messagesStroke.setData(messages, true);
            LoveChart.sendersFill.setData(sendersFillData, true);
            LoveChart.feeCountFill.setData(feeCountFillData, true);
            LoveChart.messagesDots.setData(messages, true);

            LoveChart.chart.popupTextCallback = function (xs) {
                var o = xs[0];

                var selectedMessageValue = messages[o];
                if(selectedMessageValue == undefined)
                {
                    selectedMessageValue = messages[o - 1] || 0;
                }
                var selectedSendersValue = senders[o];
                if(selectedSendersValue == undefined)
                {
                    selectedSendersValue = senders[o - 1]  || 0;
                }
                var selectedFeeCountValue = feeCount[o];
                if(selectedFeeCountValue == undefined)
                {
                    selectedFeeCountValue = feeCount[o - 1] || 0;
                }
		var dateRangeText = fullLabels[o];

                return [
                    { fill:'#F4B645', font:'bold 11px Arial, sans-serif', text:' Total fees: $'+ formatAsCurrency(Math.round(selectedMessageValue * leftMax)+'') + ' ' },
                    { fill:'#00F01B', font:'bold 11px Arial, sans-serif', text:' Unique people ' + Math.round(selectedSendersValue * rightMax) + ' '  },
                    { fill:'#00FFFB', font:'bold 11px Arial, sans-serif', text:' Fee Count: ' + Math.round(selectedFeeCountValue * rightMax) + ' ' },
                    { fill:'black', font:'bold 11px Arial, sans-serif', text: dateRangeText }
                ];
            };
        });
    },

    fetchData:undefined,

    forceWeeklyLabels: function(forceWeekly) {
    	this.forceWeekly = forceWeekly;
    },

    getData:function (from, to, username, callback) {
        if (from.getTime() > to.getTime()) {
            var tmp = from;
            from = to;
            to = tmp;
        }

	var key = from.toDateString() + ' ' + to.toDateString() + ' ' + username;
	if(this.userType != 'undefined') {
	    key = key + ' ' + this.userType;
	}

	if (!this.enableCache || !LoveChart.cache[key]) {
	  this.fetchData(from, to, username, function (messages, senders, feeCount, labels) {
	      var result = {
		  from:from,
		  to:to,
		  messages:messages,
		  senders:senders,
		  feeCount:feeCount,
		  labels:labels
	      };
	      if(this.enableCache) {
		LoveChart.cache[key] = result;
	      }
	      callback(result);
	    });
	} else {
	  callback(LoveChart.cache[key]);
	}

    },
    /**
     * Set the User type ( as Sender or Receiver )
     */
    setUserType:function (type) {
    	this.userType = type;
    }
};

}());

function formatAsCurrency(amount)
{
	var delimiter = ","; // replace comma if desired
	var a = amount.split('.',2)
	var d = '';
	if(a && a.length == 2) {
	   d = a[1];
	}
	var i = parseInt(a[0]);
	if(isNaN(i)) { return ''; }
	var minus = '';
	if(i < 0) { minus = '-'; }
	i = Math.abs(i);
	var n = new String(i);
	var a = [];
	while(n.length > 3)
	{
		var nn = n.substr(n.length-3);
		a.unshift(nn);
		n = n.substr(0,n.length-3);
	}
	if(n.length > 0) { a.unshift(n); }
	n = a.join(delimiter);
	if(d.length < 1) { amount = n; }
	else { amount = n + '.' + d; }
	amount = minus + amount;
	return amount;
}
