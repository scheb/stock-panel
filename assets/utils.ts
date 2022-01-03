export function setCookie(name: string, val: string) {
    const date = new Date();
    const value = val;

    // Set it expire in 365 days
    date.setTime(date.getTime() + (365 * 24 * 60 * 60 * 1000));

    // Set it
    document.cookie = name+"="+value+"; expires="+date.toUTCString()+"; path=/";
}

export function deleteCookie(name: string) {
    const date = new Date();

    // Set it expire in -1000 days
    date.setTime(date.getTime() + (-1000 * 24 * 60 * 60 * 1000));

    // Set it
    document.cookie = name+"=; expires="+date.toUTCString()+"; path=/";
}
