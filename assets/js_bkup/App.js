/**
 * Tooltips
 */
import Settings from './Tooltips/Settings';
import Tooltips from './Tooltips/Tooltips';
document.addEventListener("DOMContentLoaded", () => {
    Tooltips.init();
});


const Dalamud = {
    refreshTooltips: function () {
        Tooltips.refresh();
    },
    setOption: function(option, value) {
        Settings[option] = value;
    },
    getTooltips: function() {
        return Settings.storage;
    }
};

export default Dalamud;
