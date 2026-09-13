import test from 'node:test';
import assert from 'node:assert/strict';
import { hasMeaningfulDraft, contributionSteps, contributionErrors } from '../resources/js/contribute.js';

test('default dates, review checkbox and closed hours do not create a draft', () => {
    assert.equal(hasMeaningfulDraft({payload: {with_review: true, visit_date: '۱۴۰۵/۰۶/۱۸', photo_ids: [], phones: [], websites: [], weekly_hours: {saturday: {closed: true, shifts: []}}}, photos: []}), false);
});

test('selecting an existing business alone does not create a blank review draft', () => {
    assert.equal(hasMeaningfulDraft({payload: {business_id: 12, name: 'کافه', city: 'تهران', with_review: true}}), false);
    assert.equal(hasMeaningfulDraft({payload: {business_id: 12, body: '   '}}), false);
});

test('new place basics, review input and photos each qualify for recovery', () => {
    for (const payload of [{name: 'کافه'}, {city: 'تهران'}, {category_id: 1}, {business_id: 12, rating: 4}, {business_id: 12, body: 'تجربه من'}]) {
        assert.equal(hasMeaningfulDraft({payload}), true);
    }
    assert.equal(hasMeaningfulDraft({payload: {business_id: 12}, photos: [{client_id: 'photo'}]}), true);
});

test('structured place details survive recovery even without basic fields', () => {
    for (const payload of [{phones: [{label: 'اصلی', value: '02112345678'}]}, {websites: [{url: 'https://example.com'}]}, {weekly_hours: {saturday: {closed: false, shifts: []}}}]) {
        assert.equal(hasMeaningfulDraft({payload}), true);
    }
    assert.equal(hasMeaningfulDraft({}), false);
});


test('existing places skip place details but always include final confirmation', () => {
    assert.deepEqual(contributionSteps({business_id: 12, with_review: true}).map(step => step.id), [1, 3, 4]);
});

test('new places include details and an optional photo step without a review', () => {
    const steps = contributionSteps({with_review: false});
    assert.deepEqual(steps.map(step => step.id), [1, 2, 3, 4]);
    assert.equal(steps[2].label, 'عکس‌ها (اختیاری)');
});

test('editing a review cannot navigate to choosing another place', () => {
    assert.deepEqual(contributionSteps({business_id: 12, edit_review_id: 8}).map(step => step.id), [3, 4]);
});

test('missing place details are reported together before advancing', () => {
    assert.deepEqual(contributionErrors({}, 2), {
        name: ['نام مکان را وارد کن.'], category_id: ['دسته‌بندی مکان را انتخاب کن.'],
        city: ['شهر را از فهرست انتخاب کن.'], address: ['آدرس مکان را وارد کن.'],
        confirm_distinct: ['تأیید کن که این مکان یا شعبه در نتایج جست‌وجو نبود.'],
    });
});

test('review requires a valid rating and ten to two thousand characters', () => {
    for (const step of [3, 4]) {
        assert.deepEqual(Object.keys(contributionErrors({business_id: 12, with_review: true, rating: 6, body: 'کوتاه'}, step)), ['rating', 'body']);
        assert.deepEqual(contributionErrors({business_id: 12, with_review: true, rating: 5, body: 'الف'.repeat(4)}, step), {});
        assert.ok(contributionErrors({business_id: 12, with_review: true, rating: 5, body: 'الف'.repeat(700)}, step).body);
    }
});

test('place-only submissions do not require a rating or text', () => {
    const payload = {name: 'کافه', category_id: 1, city: 'تهران', address: 'خیابان اصلی', confirm_distinct: true, with_review: false};
    assert.deepEqual(contributionErrors(payload, 4), {});
    assert.deepEqual(contributionErrors({...payload, confirm_distinct: false}, 4), {confirm_distinct: ['تأیید کن که این مکان یا شعبه در نتایج جست‌وجو نبود.']});
});


test('invalid optional contacts and hours point to the exact editable field', () => {
    const payload = {business_id: null, name: 'کافه', city: 'تهران', address: 'ونک', category_id: 1, confirm_distinct: true,
        phones: [{label: '', value: 'abc'}], websites: [{label: 'اصلی', url: 'javascript:alert(1)'}],
        weekly_hours: {saturday: {closed: false, shifts: [{opens: '09:00', closes: '08:00', next_day: false}]}, sunday: {closed: false, shifts: []}}};
    const errors = contributionErrors(payload, 2);
    assert.deepEqual(Object.keys(errors), ['phones.0.label', 'phones.0.value', 'websites.0.url', 'weekly_hours.saturday.shifts.0.closes', 'weekly_hours.sunday.shifts']);
    assert.equal(errors['phones.0.label'][0], 'عنوان را وارد کن؛ مثلاً «اصلی».');
});

test('valid contacts, overnight hours, and unspecified hours can proceed', () => {
    const payload = {name: 'کافه', city: 'تهران', address: 'ونک', category_id: 1, confirm_distinct: true,
        phones: [{label: 'اصلی', value: '۰۲۱۱۲۳۴۵۶۷۸'}], websites: [{label: 'اصلی', url: 'https://example.com'}],
        weekly_hours: {saturday: {closed: false, shifts: [{opens: '20:00', closes: '02:00', next_day: true}]}}};
    assert.deepEqual(contributionErrors(payload, 2), {});
    assert.deepEqual(contributionErrors({...payload, weekly_hours: null}, 2), {});
});
