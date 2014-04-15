---
_fieldset: protected
title: Super Awesome page
_protect:
  allow:
    _addon:
      method: karma:getPoints
      comparison: '>='
      value: "50"
      error: You need 50 points
---
## {{ title }}
People *love* you. You must be really important!