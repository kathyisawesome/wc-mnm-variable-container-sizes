# WooCommerce Mix and Match: Variable Container Sizes
Validate container by chosen container size quantity

![A select input for "box size" changes the total number of products required to fill the box](https://user-images.githubusercontent.com/507025/94841617-876f4380-03d7-11eb-8148-bc1f71bb5bda.gif)

## Important

1. This is proof of concept and not officially supported in any way.
2. This is EXTRA early... the sizes are currently hard-coded, though I'd love to progress that to at least allowing customers to set the different box sizes.

## Usage

1. Upload and activate... you will get 4,8,12 as the options for each box.
2. To use different sizes you will need to edit `$options = array( 4, 8, 12 );`
3. Warning, this may not get the correct prices synced... definitely recommend setting the min nad max container sizes to the same as the min/max in the array.
