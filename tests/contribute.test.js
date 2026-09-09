import test from 'node:test';
import assert from 'node:assert/strict';
import { hasMeaningfulDraft } from '../resources/js/contribute.js';

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
