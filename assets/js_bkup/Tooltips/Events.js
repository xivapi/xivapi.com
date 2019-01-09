const Events =
{
    converted: [],
    hasConverted: function(id) {
        return this.converted.indexOf(id) > -1
    },

    eventListeners: [],
    hasEventListeners: function(id) {
        return this.eventListeners.indexOf(id) > -1
    },
};

export default Events;
