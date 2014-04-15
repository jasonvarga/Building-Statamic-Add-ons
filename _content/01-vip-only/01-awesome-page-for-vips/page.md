---
_fieldset: protected
title: Awesome page
_protect:
  allow:
    _addon:
      method: karma:getPoints
      comparison: '>='
      value: "5"
      error: You need 5 points
---
## {{ title }}
People like you. You must be important.