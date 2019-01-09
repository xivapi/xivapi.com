export default class Views
{
    static Achievement(content)
    {
        return `
            <div>${content[0]} = ${content[1]}</div>
        `;
    }

    static Item(content)
    {
        return `
            <div>${content[0]} = ${content[1]}</div>
        `;
    }
}
