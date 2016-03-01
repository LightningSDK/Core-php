lightning.intervals = {};
lightning.interval = function(timeout, callback, name){
    if (!name) {
        name = 'default';
    }

    if (!this.intervals.hasOwnProperty(name)) {
        console.log(name);
        callback();
        this.intervals[name] = false;
        setTimeout(function(){lightning.intervalRetry(name, callback)}, timeout);
    } else {
        this.intervals[name] = true;
    }
};
lightning.intervalRetry = function(name, callback){
    if (!lightning.intervals[name]) {
        delete lightning.intervals[name];
    } else {
        lightning.intervals[name] = false;
        callback();
        lightning.intervalRetry(name);
    }
};
