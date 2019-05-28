class Logger
{
    write(message)
    {
        $('.log').prepend(`<div>${message}</div>`);
    }
}

export default new Logger;
