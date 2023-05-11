// @ts-check
const { test, expect } = require('@playwright/test');
const { getAtPath } = require('../../../assets/lib');

test('getAtPath', () => {
    expect(getAtPath({}, 'name')).toBeUndefined();
    expect(getAtPath({ name: 'Alice' }, 'doesnotexist')).toBeUndefined();
    expect(getAtPath({ name: 'Alice' }, 'doesnotexist', 'default')).toBe('default');
    expect(getAtPath({ name: 'Alice' }, 'name')).toBe('Alice');
    expect(getAtPath({ name: 'Alice' }, [])).toStrictEqual({ name: 'Alice'});
    expect(getAtPath({ name: 'Alice' }, ['name'])).toBe('Alice');
    expect(getAtPath({ name: 'Alice' }, ['doesnotexist'])).toBeUndefined();
    expect(getAtPath({ user: { name: 'Alice' } }, 'user')).toStrictEqual({ name: 'Alice' });
    expect(getAtPath({ user: { name: 'Alice' } }, 'user.name')).toBe('Alice');
    expect(getAtPath({ user: { name: 'Alice' } }, ['user', 'name'])).toBe('Alice');
    expect(getAtPath({ user: { name: 'Alice' } }, ['user', 'doesnotexist'])).toBeUndefined();
});
