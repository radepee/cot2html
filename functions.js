var direction=1;
var first_time=1;
var burst_cnt = 0;
var pause_cnt = 100;
var stuck_count = 0;
var stuck_limit = 10;
var currentpos=0,alt=1,curpos1=0,curpos2=-1, oldpos=0, pause=100, waitBeforeChange=0;

var bdy;
var delta=0;

function parseQuery(queryString) 
{
    var query = {};
    var pairs = (queryString[0] === '?' ? queryString.substr(1) : queryString).split('&');
    for (var i = 0; i < pairs.length; i++) {
        var pair = pairs[i].split('=');
        query[decodeURIComponent(pair[0])] = decodeURIComponent(pair[1] || '');
    }
    return query;
}

function rebuildQuery(qry)
{
    var str = location.origin+location.pathname;
    var sep = "?";
    for (var key in qry) 
    {
        str = str + sep + key + "=" + qry[key];
        sep = "&";
    }
    return str;
}

function getZoom()
{
   return  getComputedStyle(document.body).getPropertyValue('--rescale');
}

function mickey()
{
    if (first_time)
    {
        first_time=0;
        return;
    }
    pause = 500;
    document.getElementById('autohide').style.visibility = 'visible';
}

function scro(v)
{
    if (v==2)
    {
        var qry = parseQuery(location.search); 
        qry["zoom"] = getZoom();
        location.replace(rebuildQuery(qry));
    }
    if (v==1)
    {
        var qry = parseQuery(location.search); 
        qry["scroll"] = (qry["scroll"]==0)?1:0;
        qry["zoom"] = getZoom();
        location.replace(rebuildQuery(qry));
    }
    pause = 0;
    document.getElementById('autohide').style.visibility = 'hidden';
}

function scrollwindow()
{
//    return;
    if (typeof window.orientation !== 'undefined') // MOBILE DEVICE
        return; 
    
    if (pause>0)
        pause--;
    else
	{
            document.getElementById('autohide').style.visibility = 'hidden';
	    if (pause_cnt > 0)
		pause_cnt--;
	    else
		{
		    if (burst_cnt>0)
			{
			    burst_cnt--;
			    pause_cnt = intra_burst_delay;
			}
		    else
			{
			    burst_cnt = glob_burst_length;
			    pause_cnt = extra_burst_delay;
			}
		    old = bdy.scrollTop;
		    dst = old + delta + direction*speed;
		    bdy.scrollTop = dst;
		    delta = dst - bdy.scrollTop;
		    if (old == bdy.scrollTop)
			stuck_count++;
		    else
			stuck_count=0;
		    
		    if (stuck_count > stuck_limit)
			{
//                            if (direction <0)
                            {
                                var qry = parseQuery(location.search); 
                                qry["zoom"] = getZoom();
                                location.replace(rebuildQuery(qry));
                            }
			    direction = -direction; //(bdy.scrollTop>0.5*hei)?-1:+1;
			    stuck_count = 0;
			    pause_cnt = end_delay;
			    burst_cnt = glob_burst_length;
			    delta  = 0;
			}
		}
	}
}


let root = document.documentElement;
function rescale(z)
{
    root.style.setProperty('--rescale',z);
}

function tabStart(x)
{
    var qry = parseQuery(location.search); 
    qry["tabStart"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}
function item(x)
{
    var qry = parseQuery(location.search); 
    qry["item"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

function fold(x)
{
    var qry = parseQuery(location.search); 
    qry["fold"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

function tabEnd(x)
{
    var qry = parseQuery(location.search); 
    qry["tabEnd"] = x;
    qry["zoom"] = getZoom();
    location.replace(rebuildQuery(qry));
}

function startit(scroll)
{
    ban = document.getElementById('scrollme');
    if (ban != null)
    {
        ban.onclick=mickey();
    }
    
    bdy = document.getElementById('scrollme');
    if (bdy != null && scroll)
    {
        setInterval("scrollwindow()",burst_timer);
    }
}

var zoomSlider = document.getElementById('slider-zoom');
if (zoomSlider != null)
noUiSlider.create(zoomSlider, {
    start: [getZoom()],
    range: {
        'min': [0.01],
        'max': [4.00]
    }
});

  // When the slider value changes, update the input and span
  if (zoomSlider != null)
zoomSlider.noUiSlider.on('update', function( values, handle ) 
{
    var v = values[handle];
    rescale(v);
});


    
(function (factory) {

    if ( typeof define === 'function' && define.amd ) {

        // AMD. Register as an anonymous module.
        define([], factory);

    } else if ( typeof exports === 'object' ) {

        // Node/CommonJS
        module.exports = factory();

    } else {

        // Browser globals
        window.wNumb = factory();
    }

}(function(){

	'use strict';

var FormatOptions = [
	'decimals',
	'thousand',
	'mark',
	'prefix',
	'suffix',
	'encoder',
	'decoder',
	'negativeBefore',
	'negative',
	'edit',
	'undo'
];

// General

	// Reverse a string
	function strReverse ( a ) {
		return a.split('').reverse().join('');
	}

	// Check if a string starts with a specified prefix.
	function strStartsWith ( input, match ) {
		return input.substring(0, match.length) === match;
	}

	// Check is a string ends in a specified suffix.
	function strEndsWith ( input, match ) {
		return input.slice(-1 * match.length) === match;
	}

	// Throw an error if formatting options are incompatible.
	function throwEqualError( F, a, b ) {
		if ( (F[a] || F[b]) && (F[a] === F[b]) ) {
			throw new Error(a);
		}
	}

	// Check if a number is finite and not NaN
	function isValidNumber ( input ) {
		return typeof input === 'number' && isFinite( input );
	}

	// Provide rounding-accurate toFixed method.
	// Borrowed: http://stackoverflow.com/a/21323330/775265
	function toFixed ( value, exp ) {
		value = value.toString().split('e');
		value = Math.round(+(value[0] + 'e' + (value[1] ? (+value[1] + exp) : exp)));
		value = value.toString().split('e');
		return (+(value[0] + 'e' + (value[1] ? (+value[1] - exp) : -exp))).toFixed(exp);
	}


// Formatting

	// Accept a number as input, output formatted string.
	function formatTo ( decimals, thousand, mark, prefix, suffix, encoder, decoder, negativeBefore, negative, edit, undo, input ) {

		var originalInput = input, inputIsNegative, inputPieces, inputBase, inputDecimals = '', output = '';

		// Apply user encoder to the input.
		// Expected outcome: number.
		if ( encoder ) {
			input = encoder(input);
		}

		// Stop if no valid number was provided, the number is infinite or NaN.
		if ( !isValidNumber(input) ) {
			return false;
		}

		// Rounding away decimals might cause a value of -0
		// when using very small ranges. Remove those cases.
		if ( decimals !== false && parseFloat(input.toFixed(decimals)) === 0 ) {
			input = 0;
		}

		// Formatting is done on absolute numbers,
		// decorated by an optional negative symbol.
		if ( input < 0 ) {
			inputIsNegative = true;
			input = Math.abs(input);
		}

		// Reduce the number of decimals to the specified option.
		if ( decimals !== false ) {
			input = toFixed( input, decimals );
		}

		// Transform the number into a string, so it can be split.
		input = input.toString();

		// Break the number on the decimal separator.
		if ( input.indexOf('.') !== -1 ) {
			inputPieces = input.split('.');

			inputBase = inputPieces[0];

			if ( mark ) {
				inputDecimals = mark + inputPieces[1];
			}

		} else {

		// If it isn't split, the entire number will do.
			inputBase = input;
		}

		// Group numbers in sets of three.
		if ( thousand ) {
			inputBase = strReverse(inputBase).match(/.{1,3}/g);
			inputBase = strReverse(inputBase.join( strReverse( thousand ) ));
		}

		// If the number is negative, prefix with negation symbol.
		if ( inputIsNegative && negativeBefore ) {
			output += negativeBefore;
		}

		// Prefix the number
		if ( prefix ) {
			output += prefix;
		}

		// Normal negative option comes after the prefix. Defaults to '-'.
		if ( inputIsNegative && negative ) {
			output += negative;
		}

		// Append the actual number.
		output += inputBase;
		output += inputDecimals;

		// Apply the suffix.
		if ( suffix ) {
			output += suffix;
		}

		// Run the output through a user-specified post-formatter.
		if ( edit ) {
			output = edit ( output, originalInput );
		}

		// All done.
		return output;
	}

	// Accept a sting as input, output decoded number.
	function formatFrom ( decimals, thousand, mark, prefix, suffix, encoder, decoder, negativeBefore, negative, edit, undo, input ) {

		var originalInput = input, inputIsNegative, output = '';

		// User defined pre-decoder. Result must be a non empty string.
		if ( undo ) {
			input = undo(input);
		}

		// Test the input. Can't be empty.
		if ( !input || typeof input !== 'string' ) {
			return false;
		}

		// If the string starts with the negativeBefore value: remove it.
		// Remember is was there, the number is negative.
		if ( negativeBefore && strStartsWith(input, negativeBefore) ) {
			input = input.replace(negativeBefore, '');
			inputIsNegative = true;
		}

		// Repeat the same procedure for the prefix.
		if ( prefix && strStartsWith(input, prefix) ) {
			input = input.replace(prefix, '');
		}

		// And again for negative.
		if ( negative && strStartsWith(input, negative) ) {
			input = input.replace(negative, '');
			inputIsNegative = true;
		}

		// Remove the suffix.
		// https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/slice
		if ( suffix && strEndsWith(input, suffix) ) {
			input = input.slice(0, -1 * suffix.length);
		}

		// Remove the thousand grouping.
		if ( thousand ) {
			input = input.split(thousand).join('');
		}

		// Set the decimal separator back to period.
		if ( mark ) {
			input = input.replace(mark, '.');
		}

		// Prepend the negative symbol.
		if ( inputIsNegative ) {
			output += '-';
		}

		// Add the number
		output += input;

		// Trim all non-numeric characters (allow '.' and '-');
		output = output.replace(/[^0-9\.\-.]/g, '');

		// The value contains no parse-able number.
		if ( output === '' ) {
			return false;
		}

		// Covert to number.
		output = Number(output);

		// Run the user-specified post-decoder.
		if ( decoder ) {
			output = decoder(output);
		}

		// Check is the output is valid, otherwise: return false.
		if ( !isValidNumber(output) ) {
			return false;
		}

		return output;
	}


// Framework

	// Validate formatting options
	function validate ( inputOptions ) {

		var i, optionName, optionValue,
			filteredOptions = {};

		if ( inputOptions['suffix'] === undefined ) {
			inputOptions['suffix'] = inputOptions['postfix'];
		}

		for ( i = 0; i < FormatOptions.length; i+=1 ) {

			optionName = FormatOptions[i];
			optionValue = inputOptions[optionName];

			if ( optionValue === undefined ) {

				// Only default if negativeBefore isn't set.
				if ( optionName === 'negative' && !filteredOptions.negativeBefore ) {
					filteredOptions[optionName] = '-';
				// Don't set a default for mark when 'thousand' is set.
				} else if ( optionName === 'mark' && filteredOptions.thousand !== '.' ) {
					filteredOptions[optionName] = '.';
				} else {
					filteredOptions[optionName] = false;
				}

			// Floating points in JS are stable up to 7 decimals.
			} else if ( optionName === 'decimals' ) {
				if ( optionValue >= 0 && optionValue < 8 ) {
					filteredOptions[optionName] = optionValue;
				} else {
					throw new Error(optionName);
				}

			// These options, when provided, must be functions.
			} else if ( optionName === 'encoder' || optionName === 'decoder' || optionName === 'edit' || optionName === 'undo' ) {
				if ( typeof optionValue === 'function' ) {
					filteredOptions[optionName] = optionValue;
				} else {
					throw new Error(optionName);
				}

			// Other options are strings.
			} else {

				if ( typeof optionValue === 'string' ) {
					filteredOptions[optionName] = optionValue;
				} else {
					throw new Error(optionName);
				}
			}
		}

		// Some values can't be extracted from a
		// string if certain combinations are present.
		throwEqualError(filteredOptions, 'mark', 'thousand');
		throwEqualError(filteredOptions, 'prefix', 'negative');
		throwEqualError(filteredOptions, 'prefix', 'negativeBefore');

		return filteredOptions;
	}

	// Pass all options as function arguments
	function passAll ( options, method, input ) {
		var i, args = [];

		// Add all options in order of FormatOptions
		for ( i = 0; i < FormatOptions.length; i+=1 ) {
			args.push(options[FormatOptions[i]]);
		}

		// Append the input, then call the method, presenting all
		// options as arguments.
		args.push(input);
		return method.apply('', args);
	}

	function wNumb ( options ) {

		if ( !(this instanceof wNumb) ) {
			return new wNumb ( options );
		}

		if ( typeof options !== "object" ) {
			return;
		}

		options = validate(options);

		// Call 'formatTo' with proper arguments.
		this.to = function ( input ) {
			return passAll(options, formatTo, input);
		};

		// Call 'formatFrom' with proper arguments.
		this.from = function ( input ) {
			return passAll(options, formatFrom, input);
		};
	}

	return wNumb;

}));

var FunTAB =
{
    to: function ( value )
    {
        switch (value)
        {
            case 0: return "T2";
            case 1: return "T4";
            case 2: return "T8";
            case 3: return "T16";
            case 4: return "T32";
            case 5: return "T64";
            case 6: return "T128";
            case 7: return "T256";
            default:
            return value;
        }

    },
    from: function ( value )
    {
        return value;
    }
};

var qry = parseQuery(location.search);
var tstart = -1+Math.log2(qry['tabStart']);
var tend   = -1+Math.log2(qry['tabEnd']);
var tabSlider = document.getElementById('slider-tableau');
if(tabSlider !== null)
noUiSlider.create(tabSlider, {
    start: [tend, tstart],
    direction:'rtl',
    connect:true,
    behaviour: 'tap-drag',
     tooltips: false,
    range: {
    'min' : [0,1],
    '14%' : [1,1],
    '29%' : [2,1],
    '43%' : [3,1],
    '57%' : [4,1],
    '71%' : [5,1],
    '86%' : [6,1],
    'max' : [7]
    },
        pips: { // Show a scale with the slider
        mode: 'values',
	values: [0, 1,2,3,4,5,6,7],
	density: 8,
	format: FunTAB,
    },
        format: wNumb({
	decimals: 0,
	thousand: '',
	prefix: '',
	postfix: '',
    })

});

if(tabSlider !== null)
tabSlider.noUiSlider.on('update', function( values, handle ) {
    var v0 = values[0]*1;
    var v1 = values[1]*1;
    var sta = 1<<1*(v1+1);
    var sto = 1<<1*(v0+1);
    var qry = parseQuery(location.search); 

    if (sta != qry["tabStart"] || sto != qry["tabEnd"])
    {
        qry["tabStart"] = sta;
        qry["tabEnd"]   = sto;
        qry["zoom"] = getZoom();
        location.replace(rebuildQuery(qry));
    }
});
